<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        if (Auth::user()->role === 'admin') {
            abort(403, 'Silahkan Login ke User untuk Akses Chatbot.');
        }

        return view('chatbot.index');
    }

    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'nullable|string'
        ]);

        $question = trim($request->message);
        $selectedModel = $request->model ?? 'gemini';

        // 🔹 Riwayat percakapan
        $history = session('chat_history', []);

        // 🔎 Ambil dokumen terbaru (lebih cepat & relevan)
        $docs = Document::whereNotNull('text')
            ->latest()
            ->take(100)
            ->get();

        $scores = [];

        $qtokens = array_unique(
            preg_split('/\s+/', Str::lower($question))
        );

        foreach ($docs as $doc) {

            $text = Str::lower($doc->text ?? '');
            if (!$text) continue;

            $score = 0;

            // 1️⃣ PHRASE MATCH (prioritas tinggi)
            if (Str::contains($text, Str::lower($question))) {
                $score += 50;
            }

            // 2️⃣ KEYWORD MATCH (unik)
            $matchCount = 0;
            foreach ($qtokens as $tk) {
                if (strlen($tk) < 2) continue;

                if (Str::contains($text, $tk)) {
                    $matchCount++;
                }
            }

            $score += $matchCount * 8;

            // 3️⃣ KEYWORD DENSITY
            $totalWords = str_word_count($text);
            if ($totalWords > 0) {
                $density = $matchCount / $totalWords;
                $score += $density * 100;
            }

            // 4️⃣ ORDER MATCH BONUS
            $orderedPattern = '/' . implode('.*', array_map('preg_quote', $qtokens)) . '/i';
            if (preg_match($orderedPattern, $text)) {
                $score += 15;
            }

            // 5️⃣ RECENCY BOOST
            if ($doc->created_at) {
                $daysOld = now()->diffInDays($doc->created_at);
                $score += max(0, 20 - $daysOld);
            }

            // 6️⃣ LENGTH PENALTY
            $lengthPenalty = strlen($text) / 1000;
            $score -= $lengthPenalty;

            if ($score > 0) {
                $scores[] = [
                    'doc' => $doc,
                    'score' => $score
                ];
            }
        }

        // Urutkan skor tertinggi
        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        // Ambil 3 dokumen terbaik
        $top = array_slice($scores, 0, 3);

        $context = "";

        foreach ($top as $item) {

            // 🔥 ambil bagian dokumen paling relevan
            $snippet = $this->extractRelevantSnippet(
                $item['doc']->text,
                $question
            );

            $context .= "📄 " . $item['doc']->name . ":\n"
                . $snippet . "\n====\n\n";
        }

        if (empty($context)) {
            $context = "Tidak ada dokumen relevan ditemukan. Jawab berdasarkan pengetahuan umum perusahaan.";
        }

        // 🔹 Riwayat chat
        $chatContext = "";
        foreach ($history as $turn) {
            $chatContext .= "User: {$turn['user']}\nChatbot: {$turn['bot']}\n";
        }

        // ❗ PROMPT TIDAK DIUBAH
        $prompt = "Kamu adalah asisten internal perusahaan. Jawablah berdasarkan konteks dokumen.\n\n"
            . "Konteks:\n$context\n\n"
            . "Riwayat percakapan:\n$chatContext\n\n"
            . "Pertanyaan terbaru: $question\n\n"
            . "Jawab singkat dan jelas, tetap relevan dengan konteks percakapan.";

        try {

            if ($selectedModel === 'openrouter') {

                $apiKey = env('OPENROUTER_API_KEY');
                $modelName = env('OPENROUTER_MODEL', 'qwen/qwen3-vl-235b-a22b-thinking');
                $endpoint = "https://openrouter.ai/api/v1/chat/completions";

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->post($endpoint, [
                    'model' => $modelName,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Kamu adalah asisten internal perusahaan.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

                $json = $response->json();
                $answer = $json['choices'][0]['message']['content']
                    ?? 'Maaf, saya tidak menemukan jawaban dari dokumen.';

            } else {

                $apiKey = env('GEMINI_API_KEY');
                $modelName = "gemini-2.5-flash";
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent";

                $response = Http::withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])->post($endpoint, [
                    "contents" => [
                        [
                            "parts" => [["text" => $prompt]]
                        ]
                    ]
                ]);

                $json = $response->json();
                $answer = $json['candidates'][0]['content']['parts'][0]['text']
                    ?? 'Maaf, saya tidak menemukan jawaban dari dokumen.';
            }

            // 💾 simpan riwayat
            $history[] = [
                'user' => $question,
                'bot' => $answer,
                'model' => $selectedModel
            ];
            session(['chat_history' => $history]);

        } catch (\Exception $e) {
            Log::error('Chatbot API error: ' . $e->getMessage());
            $answer = 'Terjadi kesalahan saat menghubungi layanan model ' . strtoupper($selectedModel);
        }

        return response()->json([
            'answer' => $answer,
            'model_used' => $selectedModel
        ]);
    }

    /**
     * 🔍 Ambil bagian dokumen paling relevan dengan pertanyaan
     */
    private function extractRelevantSnippet($text, $question, $radius = 600)
    {
        $textLower = Str::lower($text);
        $questionLower = Str::lower($question);

        // 1️⃣ cari frasa lengkap
        $pos = strpos($textLower, $questionLower);

        if ($pos !== false) {
            $start = max(0, $pos - $radius);
            return substr($text, $start, $radius * 2);
        }

        // 2️⃣ cari keyword penting
        $tokens = preg_split('/\s+/', $questionLower);

        foreach ($tokens as $tk) {
            if (strlen($tk) < 3) continue;

            $pos = strpos($textLower, $tk);
            if ($pos !== false) {
                $start = max(0, $pos - $radius);
                return substr($text, $start, $radius * 2);
            }
        }

        // 3️⃣ fallback: ambil awal dokumen
        return substr($text, 0, 1200);
    }
}

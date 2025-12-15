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
            'model' => 'nullable|string' // model optional
        ]);

        $question = trim($request->message);
        $selectedModel = $request->model ?? 'gemini'; // default Gemini

        Log::info('Masuk ChatbotController@ask, user_id: ' . Auth::id() . ', message: ' . $question);

        // 🔹 Ambil riwayat percakapan
        $history = session('chat_history', []);

        // 🔎 Ambil dokumen
        $docs = Document::whereNotNull('text')->get();
        $scores = [];
        $qtokens = preg_split('/\s+/', Str::lower($question));

        foreach ($docs as $doc) {
            $text = Str::lower($doc->text ?? '');
            $score = 0;
            foreach ($qtokens as $tk) {
                if (strlen($tk) < 3) continue;
                $score += substr_count($text, $tk);
            }
            if ($score > 0) $scores[] = ['doc' => $doc, 'score' => $score];
        }

        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);
        $top = array_slice($scores, 0, 3);

        $context = "";
        foreach ($top as $item) {
            $snippet = Str::limit($item['doc']->text, 10000);
            $context .= "📄 " . $item['doc']->name . ":\n" . $snippet . "\n\n";
        }

        if (empty($context)) {
            $context = "Tidak ada dokumen relevan ditemukan. Jawab berdasarkan pengetahuan umum perusahaan.";
        }

        $chatContext = "";
        foreach ($history as $turn) {
            $chatContext .= "User: {$turn['user']}\nChatbot: {$turn['bot']}\n";
        }

        $prompt = "Kamu adalah asisten internal perusahaan. Jawablah berdasarkan konteks dokumen.\n\n"
            . "Konteks:\n$context\n\n"
            . "Riwayat percakapan:\n$chatContext\n\n"
            . "Pertanyaan terbaru: $question\n\n"
            . "Jawab singkat dan jelas, tetap relevan dengan konteks percakapan.";

        try {
            if ($selectedModel === 'openrouter') {
                // ======================
                // 🧠 OPENROUTER
                // ======================
                $apiKey = env('OPENROUTER_API_KEY');
                $modelName = env('OPENROUTER_MODEL', 'meta-llama/llama-4-scout:free');
                $endpoint = "https://openrouter.ai/api/v1/chat/completions";

                Log::info("OpenRouter API Key (partial): " . substr($apiKey,0,5)."***");
                Log::info("OpenRouter endpoint: " . $endpoint);
                Log::info("OpenRouter payload: " . json_encode([
                    'model' => $modelName,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Kamu adalah asisten internal perusahaan.'],
                        ['role' => 'user', 'content' => $prompt],
                    ]
                ]));

                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ])->timeout(60)->post($endpoint, [
                    'model' => $modelName,
                    'messages' => [
                        ['role' => 'system', 'content' => 'Kamu adalah asisten internal perusahaan.'],
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

                Log::info('OpenRouter response: ' . $response->body());

                $json = $response->json();
                $answer = $json['choices'][0]['message']['content'] ?? 'Maaf, saya tidak menemukan jawaban dari dokumen.';
            } else {
                // ======================
                // 🤖 GEMINI
                // ======================
                $apiKey = env('GEMINI_API_KEY');
                $modelName = "gemini-2.5-flash";
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$modelName}:generateContent";

                Log::info("Gemini API Key (partial): " . substr($apiKey,0,5)."***");
                Log::info("Gemini endpoint: " . $endpoint);
                $payload = [
                    "contents" => [
                        ["parts" => [["text" => $prompt]]]
                    ]
                ];
                Log::info("Gemini payload: " . json_encode($payload));

                $response = Http::timeout(60)->withHeaders([
                    'Content-Type' => 'application/json',
                    'x-goog-api-key' => $apiKey,
                ])->post($endpoint, $payload);

                Log::info('Gemini response: ' . $response->body());

                $json = $response->json();
                if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
                    $answer = $json['candidates'][0]['content']['parts'][0]['text'];
                } else {
                    Log::error('Gemini API response invalid: ' . json_encode($json));
                    $answer = 'Maaf, tidak ada jawaban dari Gemini.';
                }
            }

            // 💾 Simpan riwayat
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
}

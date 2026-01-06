<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Smalot\PdfParser\Parser;
use App\Models\User;

class DocumentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth'); // Semua user harus login
    }

    public function index(Request $request)
    {
        $query = Document::query();

        // 🔍 Pencarian berdasarkan nama dokumen
        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->q . '%');
        }

        // 📅 Filter tanggal upload
        if ($request->filled('date_from')) {
            $from = Carbon::parse($request->date_from)->startOfDay();
            $query->where('uploaded_at', '>=', $from);
        }

        if ($request->filled('date_to')) {
            $to = Carbon::parse($request->date_to)->endOfDay();
            $query->where('uploaded_at', '<=', $to);
        }

        $documents = $query->orderBy('uploaded_at', 'desc')
                           ->paginate(10)
                           ->withQueryString();

        return view('documents.index', compact('documents'));
    }

    public function create()
    {
        // 🔒 Hanya admin yang boleh ke halaman upload
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
        }

        return view('documents.create');
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'admin') abort(403);

        $request->validate([
            'name' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        try {
            $file = $request->file('file');

            // ✅ SIMPAN KE RAILWAY VOLUME (/data/documents)
            $path = $file->store('', 'railway');
            $fullPath = Storage::disk('railway')->path($path);

            Log::info("📂 File tersimpan di Railway Volume: " . $fullPath);

            // Inisialisasi teks hasil parsing
            $text = null;

            // 🔹 Parsing pakai Smalot
            try {
                $parser = new Parser();
                $pdf = $parser->parseFile($fullPath);
                $text = trim($pdf->getText());

                Log::info("✅ Ekstraksi Smalot (100 char): " . substr($text, 0, 100));
            } catch (\Throwable $e) {
                Log::error("❌ Smalot PDF Parser gagal: " . $e->getMessage());
            }

            // 🔹 Fallback ke pdftotext
            if (empty($text)) {
                try {
                    $text = shell_exec(
                        "pdftotext " . escapeshellarg($fullPath) . " -"
                    );
                    Log::info("🔁 Fallback pdftotext (100 char): " . substr($text, 0, 100));
                } catch (\Throwable $e) {
                    Log::error("❌ pdftotext gagal: " . $e->getMessage());
                }
            }

            if (empty($text)) {
                Log::warning("⚠️ PDF tidak mengandung teks: " . $file->getClientOriginalName());
            }

            // Simpan ke DB ori
            $doc = Document::create([
                'name' => $request->name,
                'file_path' => $path,
                'file_original_name' => $file->getClientOriginalName(),
                'uploaded_at' => now(),
                'uploaded_by' => Auth::id(),
                'text' => $text,
            ]);

            $this->sendSecureTelegramNotification($doc);
            $this->sendFirebaseNotification(
                '📄 Dokumen Baru',
                'Admin menambahkan dokumen baru: ' . $doc->name
            );

            return redirect()
                ->route('documents.index')
                ->with('success', 'Dokumen berhasil diupload.');
        } catch (\Throwable $e) {
            Log::error("❌ Upload gagal: " . $e->getMessage());
            return back()->with('error', 'Gagal upload dokumen.');
        }
    }

    public function show(Document $document)
    {
        if (!Storage::disk('railway')->exists($document->file_path)) {
            abort(404, 'File tidak ditemukan.');
        }

        $path = Storage::disk('railway')->path($document->file_path);

        return response()->file($path);
    }


    public function destroy(Document $document)
    {
        // 🔒 Hanya admin yang boleh hapus
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Anda tidak memiliki izin untuk menghapus dokumen.');
        }

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('documents.index')
                         ->with('success', 'Dokumen berhasil dihapus.');
    }

    /**
     * 🔐 Kirim notifikasi Telegram yang aman untuk lingkungan internal
     */
    private function sendSecureTelegramNotification($doc)
    {
        $token = env('TELEGRAM_BOT_TOKEN');
        $chatId = env('TELEGRAM_CHAT_ID');

        // Jika token/chat_id tidak diset, lewati
        if (!$token || !$chatId) {
            Log::warning('Telegram token atau chat_id belum diset di .env');
            return;
        }

        // Jika masih di mode local/testing, jangan kirim ke Telegram
        if (app()->environment('local')) {
            Log::info("Simulasi notifikasi Telegram (local): Dokumen '{$doc->name}' diupload.");
            return;
        }

        $message = "📢 *Notifikasi Dokumen Baru*\n\n" .
                   "🧾 *Nama:* {$doc->name}\n" .
                   "📎 *File:* {$doc->file_original_name}\n" .
                   "👤 *Diupload oleh:* " . Auth::user()->name . "\n" .
                   "🕒 *Waktu Upload:* " . now()->format('d M Y H:i') . "\n\n" .
                   "Silakan akses dokumen melalui sistem intranet.";

        try {
            Http::timeout(5)->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Exception $e) {
            Log::error('Gagal kirim notifikasi Telegram: ' . $e->getMessage());
        }
    }
    function sendNotificationToUsers($title, $body)
    {
        $users = User::whereNotNull('fcm_token')->get();

        foreach ($users as $user) {
            Http::withHeaders([
                'Authorization' => 'Bearer ' . env('FIREBASE_SERVER_ACCESS_TOKEN'),
                'Content-Type' => 'application/json',
            ])->post(
                'https://fcm.googleapis.com/v1/projects/notification-test-b142b/messages:send',
                [
                    "message" => [
                        "token" => $user->fcm_token,
                        "notification" => [
                            "title" => $title,
                            "body" => $body
                        ]
                    ]
                ]
            );
        }
    }
}

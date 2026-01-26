<?php

namespace App\Jobs;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;


class GenerateDocumentSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $documentId) {}

    public function handle()
    {
        $doc = Document::find($this->documentId);
        if (!$doc || empty($doc->text)) return;

        try {
            $prompt = "Buatkan rangkuman dokumen berikut dalam bahasa Indonesia,
            per poin, singkat, jelas, dan formal:\n\n" .
            substr($doc->text, 0, 12000);

            // === GEMINI (reusable dari ChatbotController)
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'x-goog-api-key' => env('GEMINI_API_KEY'),
            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent",
                [
                    "contents" => [[
                        "parts" => [["text" => $prompt]]
                    ]]
                ]
            );

            $summary =
                $response->json()['candidates'][0]['content']['parts'][0]['text']
                ?? null;

            $doc->update([
                'summary' => $summary,
                'summary_status' => 'done'
            ]);

        } catch (\Throwable $e) {
            Log::error("❌ Summary AI gagal: " . $e->getMessage());
            $doc->update(['summary_status' => 'failed']);
        }
    }
}

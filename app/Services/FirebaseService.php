<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    protected string $projectId = 'notification-test-b142b';

    private function getAccessToken(): string
    {
        // Ambil isi JSON langsung dari ENV
        $serviceAccount = json_decode(base64_decode(env('FIREBASE_CREDENTIALS')), true);

        $credentials = new ServiceAccountCredentials(
            'https://www.googleapis.com/auth/firebase.messaging',
            $serviceAccount
        );

        $token = $credentials->fetchAuthToken();

        return $token['access_token'];
    }

    public function sendToToken(string $token, string $title, string $body, array $data = [])
    {
        try {
            Http::withToken($this->getAccessToken())
                ->post(
                    "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send",
                    [
                        'message' => [
                            'token' => $token,
                            'notification' => [
                                'title' => $title,
                                'body'  => $body,
                            ],
                            'data' => $data,
                        ]
                    ]
                );

            Log::info("✅ FCM sent to token");
        } catch (\Throwable $e) {
            Log::error("❌ FCM error: " . $e->getMessage());
        }
    }
}

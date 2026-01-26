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
    $json = base64_decode(env('FIREBASE_CREDENTIALS'));

    if (!$json) {
        throw new \Exception('Firebase credentials ENV kosong');
    }

    $path = storage_path('app/firebase-runtime.json');

    // tulis ulang ke file
    file_put_contents($path, $json);

    $credentials = new ServiceAccountCredentials(
        'https://www.googleapis.com/auth/firebase.messaging',
        json_decode(file_get_contents($path), true)
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

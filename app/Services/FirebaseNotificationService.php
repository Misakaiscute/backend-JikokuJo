<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class FirebaseNotificationService
{
    public function sendToToken(
        string $token,
        string $title,
        string $body,
        array $data = []
    ): bool {
        $projectId = config('services.firebase.project_id');

        if (! $projectId) {
            Log::error('FCM project id is missing. Check FIREBASE_PROJECT_ID.');
            return false;
        }

        try {
            $accessToken = $this->getAccessToken();
        } catch (\Throwable $e) {
            Log::error('Could not create FCM access token', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        $message = [
            'token' => $token,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
        ];

        if (! empty($data)) {
            $message['data'] = array_map('strval', $data);
        }

        try {
            $response = Http::withToken($accessToken)
                ->timeout(20)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::error('FCM request exception', [
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            Log::error('FCM notification failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        }

        return true;
    }

    private function getAccessToken(): string
    {
        $credentialsPath = config('services.firebase.credentials');

        if (! $credentialsPath || ! is_file($credentialsPath)) {
            throw new RuntimeException('Firebase credentials file is missing: ' . ($credentialsPath ?: 'not configured'));
        }

        $credentials = new ServiceAccountCredentials(
            ['https://www.googleapis.com/auth/firebase.messaging'],
            $credentialsPath
        );

        $token = $credentials->fetchAuthToken();

        if (empty($token['access_token'])) {
            throw new RuntimeException('Firebase access token response did not contain access_token.');
        }

        return $token['access_token'];
    }
}

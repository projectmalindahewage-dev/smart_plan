<?php

namespace App\Services;

use App\Models\TaskNotification;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class FirebasePushNotificationService
{
    public function send(User $user, TaskNotification $notification): bool
    {
        if (blank($user->fcm_token) || ! $this->isConfigured()) {
            return false;
        }

        try {
            $accessToken = $this->accessToken();
            if ($accessToken === null) {
                return false;
            }

            $response = Http::withToken($accessToken)
                ->acceptJson()
                ->post("https://fcm.googleapis.com/v1/projects/{$this->projectId()}/messages:send", [
                    'message' => [
                        'token' => $user->fcm_token,
                        'notification' => [
                            'title' => $notification->title,
                            'body' => $notification->message,
                        ],
                        'data' => array_filter([
                            'notification_id' => (string) $notification->id,
                            'type' => $notification->type,
                            'task_id' => $notification->task_id === null ? null : (string) $notification->task_id,
                            'sub_task_id' => $notification->sub_task_id === null ? null : (string) $notification->sub_task_id,
                            'status' => $notification->data['status'] ?? null,
                        ], fn ($value) => $value !== null),
                        'android' => ['priority' => 'high'],
                    ],
                ]);

            if (! $response->successful()) {
                Log::warning('Firebase push notification failed.', ['notification_id' => $notification->id, 'status' => $response->status()]);
            }

            return $response->successful();
        } catch (ConnectionException $exception) {
            Log::warning('Firebase push notification connection failed.', ['notification_id' => $notification->id, 'exception' => $exception->getMessage()]);
        } catch (Throwable $exception) {
            Log::error('Firebase push notification could not be sent.', ['notification_id' => $notification->id, 'exception' => $exception->getMessage()]);
        }

        return false;
    }

    private function isConfigured(): bool
    {
        return filled($this->projectId()) && is_readable((string) config('services.firebase.service_account_path'));
    }

    private function projectId(): ?string
    {
        $credentials = $this->credentials();

        return config('services.firebase.project_id') ?: ($credentials['project_id'] ?? null);
    }

    private function accessToken(): ?string
    {
        $credentials = $this->credentials();
        if ($credentials === []) {
            return null;
        }

        return Cache::remember('firebase-fcm-access-token', now()->addMinutes(55), function () use ($credentials) {
            $header = $this->base64Url(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
            $payload = $this->base64Url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => now()->timestamp,
                'exp' => now()->addHour()->timestamp,
            ], JSON_THROW_ON_ERROR));
            $signature = '';

            if (! openssl_sign("{$header}.{$payload}", $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                return null;
            }

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => "{$header}.{$payload}.{$this->base64Url($signature)}",
            ]);

            return $response->successful() ? $response->json('access_token') : null;
        });
    }

    private function credentials(): array
    {
        $path = config('services.firebase.service_account_path');
        if (! is_string($path) || ! is_readable($path)) {
            return [];
        }

        $credentials = json_decode((string) file_get_contents($path), true);

        return is_array($credentials) && isset($credentials['client_email'], $credentials['private_key']) ? $credentials : [];
    }

    private function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}

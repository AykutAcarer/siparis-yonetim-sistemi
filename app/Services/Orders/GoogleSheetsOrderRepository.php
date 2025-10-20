<?php

namespace App\Services\Orders;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class GoogleSheetsOrderRepository
{
    private const TOKEN_AUDIENCE = 'https://oauth2.googleapis.com/token';
    private const TOKEN_SCOPE = 'https://www.googleapis.com/auth/spreadsheets.readonly';

    /**
     * @return array{rows: array<int, array<string, mixed>>, headers: array<int, string>, source_column_present: bool}
     */
    public function getCompletedOrders(?string $spreadsheetId = null, ?string $rangeOverride = null): array
    {
        $defaults = $this->defaultChannelConfig();

        $range = $rangeOverride ?? $defaults['completed_range'];
        $spreadsheetId = $spreadsheetId ?? $defaults['spreadsheet_id'];

        return $this->fetchRange($range, $spreadsheetId);
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, headers: array<int, string>, source_column_present: bool}
     */
    public function getAbandonedOrders(?string $spreadsheetId = null, ?string $rangeOverride = null): array
    {
        $defaults = $this->defaultChannelConfig();

        $range = $rangeOverride ?? $defaults['abandoned_range'];
        $spreadsheetId = $spreadsheetId ?? $defaults['spreadsheet_id'];

        return $this->fetchRange($range, $spreadsheetId);
    }

    /**
     * @return array{rows: array<int, array<string, mixed>>, headers: array<int, string>, source_column_present: bool}
     */
    private function fetchRange(?string $range, ?string $spreadsheetId = null): array
    {
        $spreadsheetId = trim((string) ($spreadsheetId ?? ''));

        if ($spreadsheetId === '' || $range === null || $range === '') {
            throw new RuntimeException('Google Sheets configuration is incomplete.');
        }

        $values = $this->requestSheetValues($spreadsheetId, $range);

        return $this->parseValues($values);
    }

    /**
     * @return array{spreadsheet_id: ?string, completed_range: ?string, abandoned_range: ?string}
     */
    private function defaultChannelConfig(): array
    {
        $config = config('services.google_sheets');
        $channels = $config['channels'] ?? [];
        $defaultKey = strtolower($config['default_channel'] ?? 'telegram');

        $defaults = $channels[$defaultKey] ?? [];

        if (! isset($defaults['spreadsheet_id'])) {
            $defaults['spreadsheet_id'] = $config['spreadsheet_id'] ?? null;
        }

        if (! isset($defaults['completed_range'])) {
            $defaults['completed_range'] = $config['completed_range'] ?? null;
        }

        if (! isset($defaults['abandoned_range'])) {
            $defaults['abandoned_range'] = $config['abandoned_range'] ?? null;
        }

        return [
            'spreadsheet_id' => $defaults['spreadsheet_id'] ?? null,
            'completed_range' => $defaults['completed_range'] ?? null,
            'abandoned_range' => $defaults['abandoned_range'] ?? null,
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function requestSheetValues(string $spreadsheetId, string $range): array
    {
        $endpoint = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s',
            $spreadsheetId,
            rawurlencode($range)
        );

        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->timeout(10)
            ->get($endpoint);

        if ($response->failed()) {
            $message = (string) Arr::get($response->json(), 'error.message', $response->body());

            Log::warning('Google Sheets API request failed', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'message' => $message,
            ]);

            throw new RuntimeException('Failed to fetch data from Google Sheets.');
        }

        /** @var array<int, array<int, string>> $values */
        $values = $response->json('values', []);

        return $values;
    }

    /**
     * @param  array<int, array<int, string>>  $values
     * @return array{rows: array<int, array<string, mixed>>, headers: array<int, string>, source_column_present: bool}
     */
    private function parseValues(array $values): array
    {
        if ($values === []) {
            return [
                'rows' => [],
                'headers' => [],
                'source_column_present' => false,
            ];
        }

        $headerRow = array_map([$this, 'sanitizeHeader'], array_shift($values));
        $rows = [];

        foreach ($values as $row) {
            $mapped = [];

            foreach ($headerRow as $index => $header) {
                if ($header === null) {
                    continue;
                }

                $mapped[$header] = $row[$index] ?? null;
            }

            if ($this->isMeaningfulRow($mapped)) {
                $rows[] = $mapped;
            }
        }

        $sourceColumnPresent = collect($headerRow)
            ->filter()
            ->contains(fn (string $header) => strtolower($header) === 'source');

        return [
            'rows' => $rows,
            'headers' => array_values(array_filter($headerRow)),
            'source_column_present' => $sourceColumnPresent,
        ];
    }

    private function sanitizeHeader(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isMeaningfulRow(array $row): bool
    {
        return collect($row)
            ->filter(fn ($value) => $value !== null && trim((string) $value) !== '')
            ->isNotEmpty();
    }

    private function getAccessToken(): string
    {
        $cacheKey = $this->tokenCacheKey();
        $token = Cache::get($cacheKey);

        if (is_string($token) && $token !== '') {
            return $token;
        }

        $credentials = $this->getCredentials();

        $assertion = $this->buildAssertion($credentials);

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(10)
            ->post(self::TOKEN_AUDIENCE, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $assertion,
            ]);

        if ($response->failed()) {
            $message = (string) Arr::get($response->json(), 'error_description', $response->body());

            Log::error('Failed to exchange Google service account token', [
                'status' => $response->status(),
                'message' => $message,
            ]);

            throw new RuntimeException('Unable to obtain Google Sheets access token.');
        }

        $data = $response->json();
        $accessToken = (string) ($data['access_token'] ?? '');
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        if ($accessToken === '') {
            throw new RuntimeException('Google Sheets access token missing in response.');
        }

        $ttlSeconds = max(60, $expiresIn - 60);
        Cache::put($cacheKey, $accessToken, now()->addSeconds($ttlSeconds));

        return $accessToken;
    }

    /**
     * @return array<string, mixed>
     */
    private function getCredentials(): array
    {
        $path = config('services.google_sheets.credentials_path');

        if ($path === null || trim($path) === '') {
            throw new RuntimeException('Google Sheets credentials path not configured.');
        }

        if (! file_exists($path)) {
            throw new RuntimeException(sprintf('Google Sheets credentials file not found at %s.', $path));
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException('Unable to read Google Sheets credentials file.');
        }

        $credentials = json_decode($contents, true);

        if (! is_array($credentials)) {
            throw new RuntimeException('Google Sheets credentials file is not valid JSON.');
        }

        foreach (['client_email', 'private_key'] as $requiredKey) {
            if (! array_key_exists($requiredKey, $credentials) || trim((string) $credentials[$requiredKey]) === '') {
                throw new RuntimeException(sprintf('Google Sheets credentials missing [%s].', $requiredKey));
            }
        }

        return $credentials;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    private function buildAssertion(array $credentials): string
    {
        $now = Carbon::now();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $payload = [
            'iss' => $credentials['client_email'],
            'scope' => self::TOKEN_SCOPE,
            'aud' => self::TOKEN_AUDIENCE,
            'iat' => $now->timestamp,
            'exp' => $now->clone()->addMinutes(55)->timestamp,
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header, JSON_THROW_ON_ERROR)),
            $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR)),
        ];

        $signingInput = implode('.', $segments);
        $signature = $this->signAssertion($signingInput, $credentials['private_key']);

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    /**
     * @param  non-empty-string  $input
     */
    private function signAssertion(string $input, string $privateKey): string
    {
        $success = openssl_sign($input, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if ($success !== true || $signature === null) {
            throw new RuntimeException('Unable to sign Google service account assertion.');
        }

        return $signature;
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    private function tokenCacheKey(): string
    {
        $credentialsPath = config('services.google_sheets.credentials_path') ?? '';

        return 'google-sheets-token-'.md5($credentialsPath);
    }
}

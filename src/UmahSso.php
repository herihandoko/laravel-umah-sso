<?php

namespace Herihandoko\UmahSso;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UmahSso
{
    /**
     * Cookie names that must not be encrypted by Laravel.
     *
     * @return array<int, string>
     */
    public static function banprovCookieNames(): array
    {
        return [
            '_BanprovSess_v2',
            '_BanprovSessID_v2',
            '_BanprovSessSecAPI_v2',
            '_BanprovBrowseSec_v2',
            '_BanprovBrowseID_v2',
            '_BanprovSess',
            '_BanprovSessID',
            '_BanprovSessSecAPI',
            '_BanprovBrowseSec',
            '_BanprovBrowseID',
        ];
    }

    /**
     * Try SSO using Banprov session cookies forwarded to Umah auth endpoint.
     *
     * @return true|string true on success, error message string on failure
     */
    public function attempt(Request $request): bool|string
    {
        if (!config('umah-sso.enabled', true)) {
            return 'SSO Pintu Umah nonaktif.';
        }

        $authUrl = config('umah-sso.auth_url');
        if (!$authUrl) {
            return 'URL auth Umah belum dikonfigurasi.';
        }

        $cookieHeader = $this->buildBanprovCookieHeader($request);
        if ($cookieHeader === '') {
            return 'Sesi Pintu Umah tidak ditemukan. Login dulu di Pintu Umah.';
        }

        try {
            $response = Http::timeout((int) config('umah-sso.timeout', 10))
                ->withHeaders($this->authRequestHeaders($request, $cookieHeader))
                ->get($authUrl);
        } catch (\Throwable $e) {
            Log::warning('Umah SSO request failed', ['message' => $e->getMessage()]);

            return 'Tidak dapat menghubungi layanan auth Umah.';
        }

        $payload = $this->parseAuthPayload($response->body());
        if (!$payload || empty($payload['Auth'])) {
            Log::info('Umah SSO server-side auth rejected', [
                'status' => $response->status(),
                'body_prefix' => substr(trim($response->body()), 0, 120),
            ]);

            return 'Anda belum login di Pintu Umah.';
        }

        return $this->attemptFromPayload($request, $payload);
    }

    /**
     * Complete SSO from Umah auth JSON (typically fetched in the browser).
     *
     * @param  array<string, mixed>  $payload
     * @return true|string
     */
    public function attemptFromPayload(Request $request, array $payload): bool|string
    {
        if (empty($payload['Auth'])) {
            return 'Anda belum login di Pintu Umah.';
        }

        $emails = $this->extractEmails($payload);
        if (empty($emails)) {
            return 'Email BantenMail/OtherMail tidak tersedia dari sesi Umah.';
        }

        $user = $this->findUserByEmails($emails);
        if (!$user && config('umah-sso.auto_provision', false)) {
            $user = $this->provisionUser($payload, $emails);
        }

        if (!$user) {
            $appName = config('umah-sso.app_name', 'aplikasi');

            return "Akun {$appName} tidak ditemukan untuk email: " . implode(', ', $emails);
        }

        $this->loginUser($request, $user);

        $request->session()->forget(config('umah-sso.skip_session_key', 'umah_sso_skip'));

        return true;
    }

    public function hasBanprovCookies(Request $request): bool
    {
        return $this->buildBanprovCookieHeader($request) !== '';
    }

    /**
     * Server-side replay often fails when Umah binds session to the browser.
     * Fall back to a browser fetch bridge when cookies are present locally.
     */
    public function shouldUseBrowserBridge(string $message, Request $request): bool
    {
        if (!config('umah-sso.browser_sso', true)) {
            return false;
        }

        return $message === 'Anda belum login di Pintu Umah.' && $this->hasBanprovCookies($request);
    }

    /**
     * @return array<string, string>
     */
    protected function authRequestHeaders(Request $request, string $cookieHeader): array
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Cookie' => $cookieHeader,
        ];

        if ($userAgent = $request->userAgent()) {
            $headers['User-Agent'] = $userAgent;
        }

        if ($ip = $request->ip()) {
            $headers['X-Forwarded-For'] = $ip;
            $headers['X-Real-IP'] = $ip;
        }

        $headers['Referer'] = config(
            'umah-sso.auth_referer',
            'https://layanan.bantenprov.go.id/pemerintahan/'
        );

        return $headers;
    }

    /**
     * Whether the error should be shown on the login form during auto-SSO.
     */
    public function shouldSurfaceError(string $message): bool
    {
        $appName = config('umah-sso.app_name', 'aplikasi');

        return str_contains($message, "Akun {$appName} tidak ditemukan")
            || str_contains($message, 'Email BantenMail/OtherMail tidak tersedia');
    }

    protected function loginUser(Request $request, Authenticatable $user): void
    {
        if (Auth::check()) {
            Auth::logout();
        }

        foreach ((array) config('umah-sso.forget_session_keys', []) as $key) {
            $request->session()->forget($key);
        }

        Auth::login($user, (bool) config('umah-sso.remember', true));
        $request->session()->regenerate();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $emails
     */
    protected function provisionUser(array $payload, array $emails): ?Authenticatable
    {
        $provisionerClass = config('umah-sso.provisioner');

        if (!$provisionerClass || !class_exists($provisionerClass)) {
            Log::warning('Umah SSO auto-provision enabled but no provisioner configured');

            return null;
        }

        $provisioner = app($provisionerClass);

        if (!$provisioner instanceof \Herihandoko\UmahSso\Contracts\UmahSsoUserProvisioner) {
            Log::warning('Umah SSO provisioner must implement UmahSsoUserProvisioner', [
                'class' => $provisionerClass,
            ]);

            return null;
        }

        try {
            $user = $provisioner->provision($payload, $emails);
        } catch (\Throwable $e) {
            Log::error('Umah SSO auto-provision failed', [
                'message' => $e->getMessage(),
                'emails' => $emails,
            ]);

            return null;
        }

        if ($user) {
            Log::info('Umah SSO auto-provisioned user', [
                'user_id' => $user->getAuthIdentifier(),
            ]);
        }

        return $user;
    }

    /**
     * @param  array<int, string>  $emails
     */
    protected function findUserByEmails(array $emails): ?Authenticatable
    {
        $modelClass = config('umah-sso.user_model') ?: config('auth.providers.users.model');
        $column = config('umah-sso.email_column', 'email');

        if (!$modelClass || !class_exists($modelClass)) {
            return null;
        }

        /** @var Model $model */
        $model = new $modelClass;

        return $model->newQuery()
            ->where(function ($query) use ($emails, $column) {
                foreach ($emails as $email) {
                    $query->orWhereRaw('LOWER(' . $column . ') = ?', [strtolower($email)]);
                }
            })
            ->first();
    }

    protected function buildBanprovCookieHeader(Request $request): string
    {
        $raw = $request->header('Cookie', '');
        if ($raw !== '' && stripos($raw, 'Banprov') !== false) {
            $parts = [];
            foreach (explode(';', $raw) as $segment) {
                $segment = trim($segment);
                if ($segment === '') {
                    continue;
                }
                $name = explode('=', $segment, 2)[0];
                if (stripos($name, 'Banprov') !== false) {
                    $parts[] = $segment;
                }
            }
            if (!empty($parts)) {
                return implode('; ', $parts);
            }
        }

        $parts = [];
        foreach ($request->cookies->all() as $name => $value) {
            if (stripos((string) $name, 'Banprov') !== false && $value !== null && $value !== '') {
                $parts[] = $name . '=' . $value;
            }
        }

        return implode('; ', $parts);
    }

    /**
     * @return array<string, mixed>|null
     */
    protected function parseAuthPayload(string $body): ?array
    {
        $body = trim($body);
        if ($body === '' || $body[0] !== '{') {
            return null;
        }

        $data = json_decode($body, true);

        return is_array($data) ? $data : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, string>
     */
    protected function extractEmails(array $payload): array
    {
        $emails = [];
        $keys = (array) config('umah-sso.email_keys', ['BantenMail', 'OtherMail']);

        foreach ($keys as $key) {
            $email = isset($payload[$key]) ? trim((string) $payload[$key]) : '';
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $email;
            }
        }

        return array_values(array_unique($emails));
    }
}

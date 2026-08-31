<?php

namespace Herihandoko\UmahSso\Http\Controllers;

use Herihandoko\UmahSso\UmahSso;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UmahSsoController extends Controller
{
    /**
     * Explicit "Login dengan Pintu Umah" — same journey as auto SSO on /login.
     */
    public function __invoke(Request $request, UmahSso $sso)
    {
        $request->session()->forget(config('umah-sso.skip_session_key', 'umah_sso_skip'));

        if (!config('umah-sso.enabled', true)) {
            return $this->redirectWithError('SSO Pintu Umah nonaktif.');
        }

        if (!$sso->hasBanprovCookies($request)) {
            return redirect()
                ->route(config('umah-sso.login_route', 'login'))
                ->withErrors([
                    config('umah-sso.error_key', 'login_error') => 'Login dulu di Pintu Umah, lalu coba lagi.',
                ]);
        }

        $result = $sso->attempt($request);

        if ($result === true) {
            return redirect()->intended(config('umah-sso.redirect_to', '/home'));
        }

        if (is_string($result) && $sso->shouldUseBrowserBridge($result, $request)) {
            return response()->view('umah-sso::bridge', $sso->bridgeViewData());
        }

        return $this->redirectWithError(is_string($result) ? $result : 'SSO Pintu Umah gagal. Silakan login manual.');
    }

    protected function redirectWithError(string $message)
    {
        return redirect()
            ->route(config('umah-sso.login_route', 'login'))
            ->withErrors([
                config('umah-sso.error_key', 'login_error') => $message,
            ]);
    }
}

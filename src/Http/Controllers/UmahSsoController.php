<?php

namespace Herihandoko\UmahSso\Http\Controllers;

use Herihandoko\UmahSso\UmahSso;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UmahSsoController extends Controller
{
    /**
     * Entry point: GET /sso/umah (configurable).
     */
    public function __invoke(Request $request, UmahSso $sso)
    {
        // Explicit SSO click should override "skip after logout".
        $request->session()->forget(config('umah-sso.skip_session_key', 'umah_sso_skip'));

        $result = $sso->attempt($request);

        if ($result === true) {
            return redirect()->intended(config('umah-sso.redirect_to', '/home'));
        }

        if (is_string($result) && $sso->shouldUseBrowserBridge($result, $request)) {
            return response()->view('umah-sso::bridge', [
                'authUrl' => config('umah-sso.auth_url'),
                'completeUrl' => route(config('umah-sso.complete_route_name', 'sso.umah.complete')),
                'loginUrl' => route(config('umah-sso.login_route', 'login')),
            ]);
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

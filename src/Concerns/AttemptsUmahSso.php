<?php

namespace Herihandoko\UmahSso\Concerns;

use Herihandoko\UmahSso\UmahSso;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait AttemptsUmahSso
{
    /**
     * 1. Cek sesi Umah via server (layanan auth).
     * 2. Auth=true → login / auto-provision user.
     * 3. Auth=false atau tidak ada cookie → tampilkan form login.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function showLoginForm(Request $request)
    {
        /** @var UmahSso $sso */
        $sso = app(UmahSso::class);

        if (config('umah-sso.enabled') && config('umah-sso.auto_on_login')) {
            if ($sso->isReturningFromPintuUmah($request)) {
                $request->session()->forget($this->umahSsoSkipSessionKey());
            }

            if (!$sso->shouldSkipAutoSso($request)) {
                $result = $sso->attempt($request);

                if ($result === true) {
                    return redirect()->intended(config('umah-sso.redirect_to', '/home'));
                }

                if (is_string($result) && $sso->shouldUseBrowserBridge($result, $request)) {
                    return response()->view('umah-sso::bridge', $sso->bridgeViewData());
                }

                if (is_string($result) && $sso->shouldSurfaceError($result)) {
                    return $this->umahSsoLoginView()->withErrors([
                        config('umah-sso.error_key', 'login_error') => $result,
                    ]);
                }
            }
        }

        return $this->umahSsoLoginView();
    }

    /**
     * Log out of the app without auto-SSO bouncing the user back in.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash($this->umahSsoSkipSessionKey(), true);

        return redirect()->route(config('umah-sso.login_route', 'login'));
    }

    protected function umahSsoLoginView(): View
    {
        return view('auth.login');
    }

    protected function umahSsoSkipSessionKey(): string
    {
        return (string) config('umah-sso.skip_session_key', 'umah_sso_skip');
    }
}

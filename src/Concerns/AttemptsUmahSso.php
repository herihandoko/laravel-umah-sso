<?php

namespace Herihandoko\UmahSso\Concerns;

use Herihandoko\UmahSso\UmahSso;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait AttemptsUmahSso
{
    /**
     * Auto-try Pintu Umah SSO before showing the login form.
     *
     * Skipped after local logout while Pintu Umah cookies may still be present,
     * so the user is not immediately signed back into the app.
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function showLoginForm(Request $request)
    {
        if (
            config('umah-sso.enabled')
            && config('umah-sso.auto_on_login')
            && !$request->session()->get($this->umahSsoSkipSessionKey())
        ) {
            /** @var UmahSso $sso */
            $sso = app(UmahSso::class);
            $result = $sso->attempt($request);

            if ($result === true) {
                return redirect()->intended(config('umah-sso.redirect_to', '/home'));
            }

            if (is_string($result) && $sso->shouldUseBrowserBridge($result, $request)) {
                return redirect()->route(config('umah-sso.route_name', 'sso.umah'));
            }

            if (is_string($result) && $sso->shouldSurfaceError($result)) {
                return $this->umahSsoLoginView()->withErrors([
                    config('umah-sso.error_key', 'login_error') => $result,
                ]);
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
        $request->session()->put($this->umahSsoSkipSessionKey(), true);

        return redirect()->route(config('umah-sso.login_route', 'login'));
    }

    /**
     * Override in the host app if the login view path differs.
     */
    protected function umahSsoLoginView(): View
    {
        return view('auth.login');
    }

    protected function umahSsoSkipSessionKey(): string
    {
        return (string) config('umah-sso.skip_session_key', 'umah_sso_skip');
    }
}

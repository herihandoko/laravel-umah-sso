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
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\View\View
     */
    public function showLoginForm(Request $request)
    {
        if (config('umah-sso.enabled') && config('umah-sso.auto_on_login')) {
            /** @var UmahSso $sso */
            $sso = app(UmahSso::class);
            $result = $sso->attempt($request);

            if ($result === true) {
                return redirect()->intended(config('umah-sso.redirect_to', '/home'));
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
     * Override in the host app if the login view path differs.
     */
    protected function umahSsoLoginView(): View
    {
        return view('auth.login');
    }
}

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
        $result = $sso->attempt($request);

        if ($result === true) {
            return redirect()->intended(config('umah-sso.redirect_to', '/home'));
        }

        $loginRoute = config('umah-sso.login_route', 'login');
        $errorKey = config('umah-sso.error_key', 'login_error');

        return redirect()
            ->route($loginRoute)
            ->withErrors([
                $errorKey => $result ?: 'SSO Pintu Umah gagal. Silakan login manual.',
            ]);
    }
}

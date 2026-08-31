<?php

namespace Herihandoko\UmahSso\Http\Controllers;

use Herihandoko\UmahSso\UmahSso;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UmahSsoCompleteController extends Controller
{
    public function __invoke(Request $request, UmahSso $sso)
    {
        $raw = $request->input('payload');
        $payload = is_string($raw) ? json_decode($raw, true) : null;

        if (!is_array($payload)) {
            return $this->redirectWithError('Data sesi Umah tidak valid.');
        }

        $result = $sso->attemptFromPayload($request, $payload);

        if ($result === true) {
            return redirect()->intended(config('umah-sso.redirect_to', '/home'));
        }

        return $this->redirectWithError(is_string($result) ? $result : 'SSO Pintu Umah gagal.');
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

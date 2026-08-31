<?php

namespace Herihandoko\UmahSso\Http\Controllers;

use Herihandoko\UmahSso\UmahSso;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UmahSsoAuthCheckController extends Controller
{
    /**
     * Same-origin proxy to Umah auth (avoids browser CORS to layanan.bantenprov.go.id).
     */
    public function __invoke(Request $request, UmahSso $sso)
    {
        $payload = $sso->resolveAuthPayload($request);

        return response()->json($payload ?? ['Auth' => false]);
    }
}

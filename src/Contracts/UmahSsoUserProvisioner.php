<?php

namespace Herihandoko\UmahSso\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface UmahSsoUserProvisioner
{
    /**
     * Create a local user from an authenticated Umah auth payload.
     *
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $emails
     */
    public function provision(array $payload, array $emails): ?Authenticatable;
}

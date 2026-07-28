# Laravel Pintu Umah SSO

Package SSO client untuk login otomatis via sesi [Pintu Umah](https://pintu-umah.bantenprov.go.id/) / endpoint:

`https://layanan.bantenprov.go.id/v2/umah/auth`

Cocok untuk aplikasi Laravel di domain `*.bantenprov.go.id` yang dibuka dari portal layanan.

## Install

### Dari GitHub

Tambahkan di `composer.json` project:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/herihandoko/laravel-umah-sso.git"
    }
  ],
  "require": {
    "herihandoko/laravel-umah-sso": "^1.0"
  }
}
```

```bash
composer update herihandoko/laravel-umah-sso
php artisan vendor:publish --tag=umah-sso-config
```

### Dari path lokal (development)

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../laravel-umah-sso",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "herihandoko/laravel-umah-sso": "@dev"
  }
}
```

```bash
composer update herihandoko/laravel-umah-sso
```

## Env

```env
UMAH_SSO_ENABLED=true
UMAH_AUTH_URL=https://layanan.bantenprov.go.id/v2/umah/auth
UMAH_AUTH_TIMEOUT=10
UMAH_SSO_AUTO_ON_LOGIN=true
UMAH_SSO_APP_NAME=AMS
UMAH_SSO_REDIRECT=/home
```

## Pemakaian

### 1. Route SSO (otomatis)

Package mendaftarkan:

`GET /sso/umah` → name `sso.umah`

Arahkan link aplikasi di portal Umah ke URL ini (atau ke `/login` jika auto-on-login aktif).

### 2. Auto-SSO di halaman login

```php
use Herihandoko\UmahSso\Concerns\AttemptsUmahSso;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use AuthenticatesUsers, AttemptsUmahSso {
        AttemptsUmahSso::showLoginForm insteadof AuthenticatesUsers;
    }
}
```

### 3. Tombol di view login (opsional)

```blade
@if(config('umah-sso.enabled'))
    <a href="{{ route('sso.umah') }}" class="btn btn-default btn-block">
        Login dengan Pintu Umah
    </a>
@endif
```

### 4. Manual

```php
use Herihandoko\UmahSso\UmahSso;

$result = app(UmahSso::class)->attempt($request);
// true = sukses login, string = pesan error
```

## Cara kerja

1. User sudah login di Pintu Umah (cookie `_Banprov*` di `.bantenprov.go.id`).
2. User membuka aplikasi (mis. dari portal layanan).
3. Package meneruskan cookie Banprov ke endpoint auth Umah.
4. Jika `Auth: true`, cocokkan `BantenMail` / `OtherMail` ke kolom `email` user lokal.
5. Jika ketemu → `Auth::login`; jika tidak → form login + pesan error.

## Catatan

- Berjalan penuh di production domain `*.bantenprov.go.id` (cookie shared).
- Localhost / `*.test` biasanya tidak punya cookie Banprov → fallback login manual.
- Pastikan email user di aplikasi sama dengan email Umah.
- Cookie Banprov otomatis dikecualikan dari enkripsi Laravel oleh service provider.

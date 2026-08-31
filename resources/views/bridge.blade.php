<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login Pintu Umah</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #f4f6f7;
            color: #2d353c;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            margin: 0;
            padding: 24px;
        }
        .card {
            background: #fff;
            border: 1px solid #e2e7eb;
            border-radius: 8px;
            padding: 32px 28px;
            max-width: 420px;
            width: 100%;
            text-align: center;
            box-shadow: 0 8px 24px rgba(45, 53, 60, 0.08);
        }
        .spinner {
            width: 36px;
            height: 36px;
            border: 3px solid #e2e7eb;
            border-top-color: #178fc2;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 16px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        h1 { font-size: 18px; margin: 0 0 8px; }
        p { margin: 0; color: #707478; font-size: 14px; line-height: 1.5; }
        .error { color: #b91c1c; margin-top: 16px; display: none; font-size: 13px; }
        a { color: #178fc2; }
    </style>
</head>
<body>
    <div class="card">
        <div class="spinner" id="spinner"></div>
        <h1>Menghubungkan ke Pintu Umah</h1>
        <p id="status">Memverifikasi sesi Anda...</p>
        <p class="error" id="error"></p>
    </div>

    <form id="complete-form" method="POST" action="{{ $completeUrl }}" style="display:none;">
        @csrf
        <input type="hidden" name="payload" id="payload-input">
    </form>

    <script>
        (function () {
            var authCheckUrl = @json($authCheckUrl);
            var authUrl = @json($authUrl);
            var loginUrl = @json($loginUrl);
            var statusEl = document.getElementById('status');
            var errorEl = document.getElementById('error');
            var spinnerEl = document.getElementById('spinner');

            function fail(message) {
                spinnerEl.style.display = 'none';
                statusEl.textContent = 'SSO gagal';
                errorEl.style.display = 'block';
                errorEl.innerHTML = message + ' <a href="' + loginUrl + '">Kembali ke login</a>';
            }

            function completeLogin(payload) {
                if (!payload || !payload.Auth) {
                    fail('Anda belum login di Pintu Umah. Silakan login di portal layanan terlebih dahulu.');
                    return;
                }

                statusEl.textContent = 'Sesi valid. Masuk ke aplikasi...';
                document.getElementById('payload-input').value = JSON.stringify(payload);
                document.getElementById('complete-form').submit();
            }

            function parsePayload(text) {
                try {
                    return JSON.parse(String(text || '').trim());
                } catch (e) {
                    return null;
                }
            }

            function fetchAuthDirect() {
                return fetch(authUrl, {
                    method: 'GET',
                    credentials: 'include',
                    headers: { 'Accept': 'application/json, text/plain, */*' }
                }).then(function (response) {
                    return response.text().then(function (text) {
                        return parsePayload(text);
                    });
                });
            }

            function fetchAuthViaApp() {
                return fetch(authCheckUrl, {
                    method: 'GET',
                    credentials: 'include',
                    headers: { 'Accept': 'application/json' }
                }).then(function (response) {
                    return response.json();
                });
            }

            fetchAuthViaApp()
                .then(function (payload) {
                    if (payload && payload.Auth) {
                        completeLogin(payload);
                        return;
                    }

                    return fetchAuthDirect().then(completeLogin);
                })
                .catch(function () {
                    fetchAuthDirect()
                        .then(completeLogin)
                        .catch(function () {
                            fail('Tidak dapat menghubungi layanan auth Umah. Pastikan Anda sudah login di Pintu Umah.');
                        });
                });
        })();
    </script>
</body>
</html>

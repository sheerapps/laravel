<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram Login - SheerApps 4D</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }
        .logo {
            font-size: 32px;
            font-weight: bold;
            color: #0088cc;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .telegram-btn {
            background: #0088cc;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            transition: all 0.3s ease;
        }
        .telegram-btn:hover {
            background: #0077b3;
            transform: translateY(-2px);
        }
        .telegram-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
        }
        .loading {
            display: none;
            margin-top: 20px;
        }
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0088cc;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 0 auto 10px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .error {
            color: #e74c3c;
            margin-top: 15px;
            display: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">4D SheerApps</div>
        <div class="subtitle">Live 4D Results & 4D Forecast</div>
        
        <button id="telegramBtn" class="telegram-btn" onclick="initTelegramLogin()">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
            </svg>
            Login with Telegram
        </button>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <div>Processing login...</div>
        </div>
        
        <div class="error" id="error"></div>
    </div>

    <script>
        let tg = window.Telegram.WebApp;
        
        // Initialize Telegram WebApp
        tg.ready();
        tg.expand();
        
        function initTelegramLogin() {
            const btn = document.getElementById('telegramBtn');
            const loading = document.getElementById('loading');
            const error = document.getElementById('error');
            
            btn.disabled = true;
            loading.style.display = 'block';
            error.style.display = 'none';
            
            try {
                // Get user data from Telegram WebApp
                const user = tg.initDataUnsafe?.user;
                const initData = tg.initData;
                
                if (!user || !initData) {
                    throw new Error('Telegram WebApp data not available');
                }
                
                // Prepare login data
                const loginData = {
                    id: user.id,
                    first_name: user.first_name,
                    username: user.username || '',
                    photo_url: user.photo_url || '',
                    hash: initData,
                    referrer_id: getUrlParameter('referrer_id') || null
                };
                
                // Send login request
                fetch('/api/telegram-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify(loginData)
                })
                .then(response => {
                    if (response.redirected) {
                        // Handle redirect to React Native app
                        window.location.href = response.url;
                    } else {
                        return response.json();
                    }
                })
                .then(data => {
                    if (data && data.status === 'success') {
                        // Success - redirect to React Native app
                        const redirectUrl = buildRedirectUrl(data);
                        window.location.href = redirectUrl;
                    } else {
                        throw new Error(data?.message || 'Login failed');
                    }
                })
                .catch(err => {
                    console.error('Login error:', err);
                    showError(err.message || 'An error occurred during login');
                })
                .finally(() => {
                    btn.disabled = false;
                    loading.style.display = 'none';
                });
                
            } catch (err) {
                console.error('Telegram WebApp error:', err);
                showError('Telegram WebApp is not available. Please open this page from Telegram.');
                btn.disabled = false;
                loading.style.display = 'none';
            }
        }
        
        function buildRedirectUrl(data) {
            const params = new URLSearchParams({
                username: data.user?.username || data.user?.name || 'User',
                avatar: data.user?.photo_url || '',
                status: data.user?.status || 'active',
                token: data.token,
                user_id: data.user?.id || '',
                referrer_id: data.user?.referrer_id || '',
                referral_count: data.user?.referral_count || '0'
            });
            
            return `sheerapps4d://telegram-login-success?${params.toString()}`;
        }
        
        function getUrlParameter(name) {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get(name);
        }
        
        function showError(message) {
            const error = document.getElementById('error');
            error.textContent = message;
            error.style.display = 'block';
        }
        
        // Auto-init if Telegram WebApp is ready
        if (tg.initDataUnsafe?.user) {
            initTelegramLogin();
        }
    </script>
</body>
</html>

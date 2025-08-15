<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $appName }} - Telegram Login</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
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
            width: 80px;
            height: 80px;
            background: #0088cc;
            border-radius: 50%;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            font-weight: bold;
        }
        
        .title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            line-height: 1.5;
        }
        
        .telegram-button {
            background: #0088cc;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,136,204,0.3);
        }
        
        .telegram-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,136,204,0.4);
        }
        
        .telegram-button:active {
            transform: translateY(0);
        }
        
        .telegram-icon {
            width: 24px;
            height: 24px;
            margin-right: 10px;
        }
        
        .loading {
            display: none;
            margin: 20px 0;
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #0088cc;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 15px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .error {
            color: #e74c3c;
            background: #fdf2f2;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            display: none;
        }
        
        .referral-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 20px 0;
            border-left: 4px solid #0088cc;
        }
        
        .referral-text {
            color: #666;
            font-size: 14px;
        }
        
        .footer {
            margin-top: 30px;
            color: #999;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">4D</div>
        <h1 class="title">{{ $appName }}</h1>
        <p class="subtitle">Login with your Telegram account to continue</p>
        
        @if($referralId)
        <div class="referral-info">
            <p class="referral-text">
                <strong>Referral Detected!</strong><br>
                You were invited by another user. This will be recorded in your account.
            </p>
        </div>
        @endif
        
        <button class="telegram-button" onclick="initTelegramLogin()">
            <svg class="telegram-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221l-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.446 1.394c-.14.18-.357.295-.6.295-.002 0-.003 0-.005 0l.213-3.054 5.56-5.022c.24-.213-.054-.334-.373-.121l-6.869 4.326-2.96-.924c-.64-.203-.658-.64.135-.954l11.566-4.458c.538-.196 1.006.128.832.941z"/>
            </svg>
            Login with Telegram
        </button>
        
        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Connecting to Telegram...</p>
        </div>
        
        <div class="error" id="error"></div>
        
        <div class="footer">
            <p>Secure login powered by Telegram</p>
        </div>
    </div>

    <script>
        let tg = null;
        
        // Initialize Telegram WebApp
        function initTelegram() {
            try {
                tg = window.Telegram.WebApp;
                tg.ready();
                tg.expand();
                
                // Set theme
                if (tg.colorScheme === 'dark') {
                    document.body.style.background = 'linear-gradient(135deg, #2c3e50 0%, #34495e 100%)';
                    document.querySelector('.container').style.background = '#2c3e50';
                    document.querySelector('.title').style.color = '#ecf0f1';
                    document.querySelector('.subtitle').style.color = '#bdc3c7';
                }
            } catch (e) {
                console.log('Telegram WebApp not available:', e);
            }
        }
        
        // Initialize Telegram login
        function initTelegramLogin() {
            try {
                if (tg && tg.initData) {
                    // We have Telegram data, process login
                    processTelegramLogin(tg.initData);
                } else {
                    // No Telegram data, show error
                    showError('Please open this page from Telegram to login.');
                }
            } catch (e) {
                showError('Failed to initialize Telegram login: ' + e.message);
            }
        }
        
        // Process Telegram login data
        function processTelegramLogin(initData) {
            showLoading(true);
            
            // Parse init data
            const data = new URLSearchParams(initData);
            const userData = {};
            
            // Extract user information
            for (const [key, value] of data.entries()) {
                userData[key] = value;
            }
            
            // Add referral ID if available
            @if($referralId)
            userData.referrer_id = '{{ $referralId }}';
            @endif
            
            // Send login request
            fetch('/api/telegram-login/auth', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(userData)
            })
            .then(response => {
                if (response.redirected) {
                    // Success - redirect to React Native app
                    window.location.href = response.url;
                } else {
                    return response.json();
                }
            })
            .then(data => {
                if (data && data.status === 'error') {
                    showError(data.message || 'Login failed');
                }
            })
            .catch(error => {
                console.error('Login error:', error);
                showError('Login failed. Please try again.');
            })
            .finally(() => {
                showLoading(false);
            });
        }
        
        // Show/hide loading
        function showLoading(show) {
            document.getElementById('loading').style.display = show ? 'block' : 'none';
            document.querySelector('.telegram-button').style.display = show ? 'none' : 'flex';
        }
        
        // Show error
        function showError(message) {
            const errorDiv = document.getElementById('error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
            showLoading(false);
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initTelegram();
        });
        
        // Handle page visibility change
        document.addEventListener('visibilitychange', function() {
            if (document.visibilityState === 'visible') {
                // Page became visible, check if we have Telegram data
                if (tg && tg.initData) {
                    initTelegramLogin();
                }
            }
        });
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login with Telegram - SheerApps 4D</title>
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
        }
        
        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 20px;
            background: #0088cc;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
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
            gap: 10px;
            width: 100%;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }
        
        .telegram-button:hover {
            background: #0077b3;
            transform: translateY(-2px);
        }
        
        .telegram-button:active {
            transform: translateY(0);
        }
        
        .telegram-icon {
            width: 24px;
            height: 24px;
        }
        
        .loading {
            display: none;
            color: #666;
            margin-top: 20px;
        }
        
        .error {
            color: #e74c3c;
            margin-top: 20px;
            display: none;
        }
        
        .success {
            color: #27ae60;
            margin-top: 20px;
            display: none;
        }
        
        .referral-input {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .referral-input:focus {
            outline: none;
            border-color: #0088cc;
        }
        
        .referral-label {
            color: #666;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .test-mode-toggle {
            margin-top: 20px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .test-mode-toggle label {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 14px;
            color: #666;
        }
        
        .test-inputs {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 10px;
            border: 1px solid #e0e0e0;
        }
        
        .test-inputs.show {
            display: block;
        }
        
        .test-input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .test-input:focus {
            outline: none;
            border-color: #0088cc;
        }
        
        .test-label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-size: 12px;
            color: #666;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📱</div>
        <h1 class="title">SheerApps 4D</h1>
        <p class="subtitle">Login with your Telegram account to access live 4D results and forecasts</p>
        
        <div class="referral-label">Referral ID (Optional):</div>
        <input type="number" id="referralId" class="referral-input" placeholder="Enter referral ID if you have one">
        
        <button id="loginBtn" class="telegram-button" onclick="initTelegramLogin()">
            <svg class="telegram-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm4.64 6.8c-.15 1.58-.8 5.42-1.13 7.19-.14.75-.42 1-.68 1.03-.58.05-1.02-.38-1.58-.75-.88-.58-1.38-.94-2.23-1.5-.99-.65-.35-1.01.22-1.59.15-.15 2.71-2.48 2.76-2.69a.2.2 0 00-.05-.18c-.06-.05-.14-.03-.21-.02-.09.02-1.49.95-4.22 2.79-.4.27-.76.41-1.08.4-.36-.01-1.04-.2-1.55-.37-.63-.2-1.12-.31-1.08-.66.02-.18.27-.36.74-.55 2.92-1.27 4.86-2.11 5.83-2.51 2.78-1.16 3.35-1.36 3.73-1.36.08 0 .27.02.39.12.1.08.13.19.14.27-.01.06-.01.13-.02.2z"/>
            </svg>
            Login with Telegram
        </button>
        
        <div id="loading" class="loading">
            <p>Connecting to Telegram...</p>
        </div>
        
        <div id="error" class="error"></div>
        <div id="success" class="success"></div>
        
        <!-- Test Mode Toggle -->
        <div class="test-mode-toggle">
            <label>
                <input type="checkbox" id="testModeToggle" onchange="toggleTestMode()">
                Enable Test Mode (for development)
            </label>
        </div>
        
        <!-- Test Mode Inputs -->
        <div id="testInputs" class="test-inputs">
            <label class="test-label">Test User ID:</label>
            <input type="number" id="testUserId" class="test-input" placeholder="123456789" value="123456789">
            
            <label class="test-label">Test First Name:</label>
            <input type="text" id="testFirstName" class="test-input" placeholder="John" value="John">
            
            <label class="test-label">Test Username:</label>
            <input type="text" id="testUsername" class="test-input" placeholder="john_doe" value="john_doe">
            
            <label class="test-label">Test Photo URL:</label>
            <input type="url" id="testPhotoUrl" class="test-input" placeholder="https://example.com/photo.jpg" value="https://t.me/i/userpic/320/photo.jpg">
            
            <button id="testLoginBtn" class="telegram-button" onclick="initTestLogin()" style="margin-top: 10px;">
                Test Login
            </button>
        </div>
    </div>

    <script>
        let tg = null;
        let isTestMode = false;
        
        // Initialize Telegram WebApp
        function initTelegram() {
            if (window.Telegram && window.Telegram.WebApp) {
                tg = window.Telegram.WebApp;
                tg.ready();
                tg.expand();
                
                // Set theme
                if (tg.colorScheme === 'dark') {
                    document.body.style.background = 'linear-gradient(135deg, #2c3e50 0%, #34495e 100%)';
                    document.querySelector('.container').style.background = '#34495e';
                    document.querySelector('.title').style.color = '#ecf0f1';
                    document.querySelector('.subtitle').style.color = '#bdc3c7';
                }
                
                console.log('Telegram WebApp initialized successfully');
                console.log('User data:', tg.initDataUnsafe?.user);
            } else {
                console.log('Telegram WebApp not available - running in test mode');
                isTestMode = true;
                document.getElementById('testModeToggle').checked = true;
                toggleTestMode();
            }
        }
        
        // Toggle test mode
        function toggleTestMode() {
            const testInputs = document.getElementById('testInputs');
            const testModeToggle = document.getElementById('testModeToggle');
            
            if (testModeToggle.checked) {
                testInputs.classList.add('show');
                isTestMode = true;
            } else {
                testInputs.classList.remove('show');
                isTestMode = false;
            }
        }
        
        // Handle Telegram login
        function initTelegramLogin() {
            if (isTestMode) {
                showError('Please use Test Login button when in test mode');
                return;
            }
            
            const referralId = document.getElementById('referralId').value.trim();
            const loginBtn = document.getElementById('loginBtn');
            const loading = document.getElementById('loading');
            
            // Show loading state
            loginBtn.style.display = 'none';
            loading.style.display = 'block';
            hideError();
            hideSuccess();
            
            try {
                // Get user data from Telegram
                const user = tg?.initDataUnsafe?.user;
                
                if (!user) {
                    showError('Failed to get user data from Telegram. Please try again.');
                    resetUI();
                    return;
                }
                
                console.log('Telegram user data:', user);
                
                // Prepare data for API
                const loginData = {
                    id: user.id,
                    first_name: user.first_name || '',
                    username: user.username || '',
                    photo_url: user.photo_url || '',
                    hash: tg.initData,
                    referrer_id: referralId || null
                };
                
                console.log('Sending login data:', loginData);
                
                // Send to Laravel API
                fetch('/api/telegram-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(loginData)
                })
                .then(response => {
                    console.log('API response:', response);
                    if (response.redirected) {
                        // Handle redirect to React Native app
                        const redirectUrl = response.url;
                        console.log('Redirect URL:', redirectUrl);
                        if (redirectUrl.startsWith('sheerapps4d://')) {
                            showSuccess('Login successful! Redirecting to app...');
                            setTimeout(() => {
                                window.location.href = redirectUrl;
                            }, 1000);
                        } else {
                            showError('Unexpected redirect response');
                            resetUI();
                        }
                    } else {
                        return response.json();
                    }
                })
                .then(data => {
                    if (data) {
                        console.log('API response data:', data);
                        if (data.status === 'success') {
                            showSuccess('Login successful!');
                        } else {
                            showError(data.message || 'Login failed');
                            resetUI();
                        }
                    }
                })
                .catch(error => {
                    console.error('Login error:', error);
                    showError('Network error. Please check your connection and try again.');
                    resetUI();
                });
                
            } catch (error) {
                console.error('Telegram login error:', error);
                showError('Failed to process Telegram login. Please try again.');
                resetUI();
            }
        }
        
        // Handle test login
        function initTestLogin() {
            const referralId = document.getElementById('referralId').value.trim();
            const testUserId = document.getElementById('testUserId').value.trim();
            const testFirstName = document.getElementById('testFirstName').value.trim();
            const testUsername = document.getElementById('testUsername').value.trim();
            const testPhotoUrl = document.getElementById('testPhotoUrl').value.trim();
            
            if (!testUserId || !testFirstName) {
                showError('Please fill in at least User ID and First Name for test mode');
                return;
            }
            
            const loginBtn = document.getElementById('testLoginBtn');
            const loading = document.getElementById('loading');
            
            // Show loading state
            loginBtn.style.display = 'none';
            loading.style.display = 'block';
            hideError();
            hideSuccess();
            
            try {
                // Prepare test data for API
                const loginData = {
                    id: parseInt(testUserId),
                    first_name: testFirstName,
                    username: testUsername || '',
                    photo_url: testPhotoUrl || '',
                    hash: 'test_hash_' + Date.now(), // Generate test hash
                    referrer_id: referralId || null
                };
                
                console.log('Sending test login data:', loginData);
                
                // Send to Laravel API
                fetch('/api/telegram-login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify(loginData)
                })
                .then(response => {
                    console.log('Test API response:', response);
                    if (response.redirected) {
                        // Handle redirect to React Native app
                        const redirectUrl = response.url;
                        console.log('Test redirect URL:', redirectUrl);
                        if (redirectUrl.startsWith('sheerapps4d://')) {
                            showSuccess('Test login successful! Redirecting to app...');
                            setTimeout(() => {
                                window.location.href = redirectUrl;
                            }, 1000);
                        } else {
                            showError('Unexpected redirect response');
                            resetUI();
                        }
                    } else {
                        return response.json();
                    }
                })
                .then(data => {
                    if (data) {
                        console.log('Test API response data:', data);
                        if (data.status === 'success') {
                            showSuccess('Test login successful!');
                        } else {
                            showError(data.message || 'Test login failed');
                            resetUI();
                        }
                    }
                })
                .catch(error => {
                    console.error('Test login error:', error);
                    showError('Network error. Please check your connection and try again.');
                    resetUI();
                });
                
            } catch (error) {
                console.error('Test login error:', error);
                showError('Failed to process test login. Please try again.');
                resetUI();
            }
        }
        
        // UI helper functions
        function showError(message) {
            const errorDiv = document.getElementById('error');
            errorDiv.textContent = message;
            errorDiv.style.display = 'block';
        }
        
        function hideError() {
            document.getElementById('error').style.display = 'none';
        }
        
        function showSuccess(message) {
            const successDiv = document.getElementById('success');
            successDiv.textContent = message;
            successDiv.style.display = 'block';
        }
        
        function hideSuccess() {
            document.getElementById('success').style.display = 'none';
        }
        
        function resetUI() {
            document.getElementById('loginBtn').style.display = 'flex';
            document.getElementById('testLoginBtn').style.display = 'flex';
            document.getElementById('loading').style.display = 'none';
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initTelegram();
            
            // Handle referral ID from URL
            const urlParams = new URLSearchParams(window.location.search);
            const refId = urlParams.get('ref') || urlParams.get('referral_id');
            if (refId) {
                document.getElementById('referralId').value = refId;
            }
            
            // Check if we're in a WebView (React Native)
            const isInWebView = window.ReactNativeWebView || /ReactNativeWebView/.test(navigator.userAgent);
            if (isInWebView) {
                console.log('Running in React Native WebView');
                // Enable test mode by default in WebView for easier testing
                document.getElementById('testModeToggle').checked = true;
                toggleTestMode();
            }
        });
        
        // Handle back button
        if (window.history && window.history.pushState) {
            window.addEventListener('popstate', function() {
                if (tg) {
                    tg.close();
                }
            });
        }
    </script>
</body>
</html>

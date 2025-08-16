<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram OAuth Callback - SheerApps 4D</title>
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
            max-width: 500px;
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
        
        .status {
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .status.loading {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .info {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: left;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
            border-bottom: 1px solid #e9ecef;
        }
        
        .info-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .info-label {
            font-weight: 500;
            color: #495057;
        }
        
        .info-value {
            color: #6c757d;
            font-family: monospace;
        }
        
        .redirect-button {
            background: #28a745;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 25px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            margin-top: 20px;
        }
        
        .redirect-button:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #0088cc;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📱</div>
        <h1 class="title">Telegram OAuth Callback</h1>
        <p class="subtitle">Processing your login request...</p>
        
        <div id="status" class="status loading">
            <div class="loading-spinner"></div>
            Processing Telegram OAuth callback...
        </div>
        
        <div id="info" class="info" style="display: none;">
            <div class="info-row">
                <span class="info-label">Referral Code:</span>
                <span class="info-value" id="referralCode">-</span>
            </div>
            <div class="info-row">
                <span class="info-label">User ID:</span>
                <span class="info-value" id="userId">-</span>
            </div>
            <div class="info-row">
                <span class="info-label">Username:</span>
                <span class="info-value" id="username">-</span>
            </div>
            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value" id="userStatus">-</span>
            </div>
        </div>
        
        <div id="redirectSection" style="display: none;">
            <p>Login successful! Redirecting to your app...</p>
            <a id="redirectButton" href="#" class="redirect-button">
                <span>Open App</span>
            </a>
        </div>
    </div>

    <script>
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const referralCode = urlParams.get('referral_code');
        const telegramData = urlParams.get('telegram_data');
        
        // Update status and info
        document.getElementById('referralCode').textContent = referralCode || 'Not provided';
        
        // Simulate processing delay
        setTimeout(() => {
            // Check if we have Telegram data (in real implementation, this would come from Telegram OAuth)
            if (telegramData) {
                try {
                    const data = JSON.parse(decodeURIComponent(telegramData));
                    document.getElementById('userId').textContent = data.id || 'Unknown';
                    document.getElementById('username').textContent = data.first_name || 'Unknown';
                    document.getElementById('userStatus').textContent = 'Active';
                    
                    // Show success status
                    const statusDiv = document.getElementById('status');
                    statusDiv.className = 'status success';
                    statusDiv.innerHTML = '✅ Login successful!';
                    
                    // Show info
                    document.getElementById('info').style.display = 'block';
                    
                    // Show redirect section
                    document.getElementById('redirectSection').style.display = 'block';
                    
                    // Build redirect URL to React Native app
                    const redirectUrl = `sheerapps4d://telegram-login-success?username=${encodeURIComponent(data.first_name || 'User')}&avatar=${encodeURIComponent(data.photo_url || '')}&status=active&token=oauth_token_${Date.now()}&user_id=${data.id}&referrer_id=${referralCode || ''}&referral_count=0`;
                    
                    document.getElementById('redirectButton').href = redirectUrl;
                    
                    // Auto-redirect after 3 seconds
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 3000);
                    
                } catch (error) {
                    console.error('Error parsing Telegram data:', error);
                    showError('Failed to parse user data');
                }
            } else {
                // Simulate successful login for demo purposes
                document.getElementById('userId').textContent = '123456789';
                document.getElementById('username').textContent = 'Demo User';
                document.getElementById('userStatus').textContent = 'Active';
                
                // Show success status
                const statusDiv = document.getElementById('status');
                statusDiv.className = 'status success';
                statusDiv.innerHTML = '✅ Login successful!';
                
                // Show info
                document.getElementById('info').style.display = 'block';
                
                // Show redirect section
                document.getElementById('redirectSection').style.display = 'block';
                
                // Build redirect URL to React Native app
                const redirectUrl = `sheerapps4d://telegram-login-success?username=${encodeURIComponent('Demo User')}&avatar=${encodeURIComponent('')}&status=active&token=demo_token_${Date.now()}&user_id=123456789&referrer_id=${referralCode || ''}&referral_count=0`;
                
                document.getElementById('redirectButton').href = redirectUrl;
                
                // Auto-redirect after 3 seconds
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 3000);
            }
        }, 2000);
        
        function showError(message) {
            const statusDiv = document.getElementById('status');
            statusDiv.className = 'status error';
            statusDiv.innerHTML = `❌ ${message}`;
        }
    </script>
</body>
</html>

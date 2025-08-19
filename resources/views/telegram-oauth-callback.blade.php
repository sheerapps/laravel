<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram OAuth Callback</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section {
            margin-bottom: 20px;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .section h3 {
            margin-top: 0;
            color: #333;
        }
        .data-item {
            margin: 5px 0;
            padding: 5px;
            background-color: #f9f9f9;
            border-radius: 3px;
        }
        .button {
            background-color: #0088cc;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        .button:hover {
            background-color: #006699;
        }
        .button.success {
            background-color: #28a745;
        }
        .button.danger {
            background-color: #dc3545;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .status.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Telegram OAuth Callback</h1>
        
        <div class="section">
            <h3>📊 Request Information</h3>
            <div class="data-item"><strong>Full URL:</strong> <span id="fullUrl">{{ request()->fullUrl() }}</span></div>
            <div class="data-item"><strong>Query Parameters:</strong> <span id="queryParams">{{ json_encode(request()->query()) }}</span></div>
            <div class="data-item"><strong>URL Fragment:</strong> <span id="urlFragment">{{ request()->fragment() }}</span></div>
        </div>

        <div class="section">
            <h3>🔍 Telegram OAuth Data Extraction</h3>
            <div id="extractionStatus" class="status info">Extracting data from URL fragment...</div>
            <div id="extractedData"></div>
        </div>

        <div class="section">
            <h3>🎯 Actions</h3>
            <button class="button success" onclick="redirectToApp()">✅ Redirect to App (Success)</button>
            <button class="button danger" onclick="redirectToAppError()">❌ Redirect to App (Error)</button>
            <button class="button" onclick="extractAndProcess()">🔄 Extract & Process Data</button>
        </div>

        <div class="section">
            <h3>📝 Debug Information</h3>
            <div id="debugInfo"></div>
        </div>
    </div>

    <script>
        // Extract Telegram OAuth data from URL fragment
        function extractTelegramData() {
            const urlFragment = window.location.hash;
            console.log('URL Fragment:', urlFragment);
            
            if (!urlFragment) {
                return null;
            }
            
            // Remove the # symbol
            const fragmentData = urlFragment.substring(1);
            console.log('Fragment Data:', fragmentData);
            
            // Check if it's a tgAuthResult
            if (fragmentData.startsWith('tgAuthResult=')) {
                const encodedData = fragmentData.substring('tgAuthResult='.length);
                console.log('Encoded Data:', encodedData);
                
                try {
                    // Decode the base64 data
                    const decodedData = atob(encodedData);
                    console.log('Decoded Data:', decodedData);
                    
                    // Parse the JSON
                    const userData = JSON.parse(decodedData);
                    console.log('Parsed User Data:', userData);
                    
                    return userData;
                } catch (error) {
                    console.error('Error parsing Telegram data:', error);
                    return null;
                }
            }
            
            return null;
        }

        // Process the extracted data
        function extractAndProcess() {
            const telegramData = extractTelegramData();
            const statusDiv = document.getElementById('extractionStatus');
            const dataDiv = document.getElementById('extractedData');
            
            if (telegramData) {
                statusDiv.className = 'status success';
                statusDiv.textContent = '✅ Telegram data extracted successfully!';
                
                dataDiv.innerHTML = `
                    <h4>📱 Extracted User Data:</h4>
                    <div class="data-item"><strong>ID:</strong> ${telegramData.id}</div>
                    <div class="data-item"><strong>First Name:</strong> ${telegramData.first_name}</div>
                    <div class="data-item"><strong>Last Name:</strong> ${telegramData.last_name || 'N/A'}</div>
                    <div class="data-item"><strong>Username:</strong> ${telegramData.username || 'N/A'}</div>
                    <div class="data-item"><strong>Photo URL:</strong> ${telegramData.photo_url || 'N/A'}</div>
                    <div class="data-item"><strong>Auth Date:</strong> ${new Date(telegramData.auth_date * 1000).toLocaleString()}</div>
                    <div class="data-item"><strong>Hash:</strong> ${telegramData.hash}</div>
                `;
                
                // Store the data for later use
                window.telegramUserData = telegramData;
                
                console.log('Telegram data stored:', telegramData);
            } else {
                statusDiv.className = 'status error';
                statusDiv.textContent = '❌ Failed to extract Telegram data from URL fragment';
                dataDiv.innerHTML = '<p>No valid Telegram OAuth data found in URL fragment.</p>';
            }
        }

        // Redirect to app with success
        function redirectToApp() {
            const telegramData = window.telegramUserData || extractTelegramData();
            
            if (telegramData) {
                // Build the success deep link with actual user data
                const params = new URLSearchParams({
                    username: telegramData.username || telegramData.first_name,
                    avatar: telegramData.photo_url || '',
                    status: 'active',
                    token: 'generated_token_' + Date.now(), // In real app, this would be generated by Laravel
                    user_id: telegramData.id,
                    referrer_id: getReferralCodeFromUrl() || '',
                    referral_count: '0'
                });
                
                const deepLink = `sheerapps4d://telegram-login-success?${params.toString()}`;
                console.log('Redirecting to app with success:', deepLink);
                
                // Redirect to the app
                window.location.href = deepLink;
            } else {
                // Fallback to test data
                const testParams = new URLSearchParams({
                    username: 'Test User',
                    avatar: '',
                    status: 'active',
                    token: 'test_token_' + Date.now(),
                    user_id: '12345',
                    referrer_id: getReferralCodeFromUrl() || '',
                    referral_count: '0'
                });
                
                const testDeepLink = `sheerapps4d://telegram-login-success?${testParams.toString()}`;
                console.log('Redirecting to app with test data:', testDeepLink);
                
                window.location.href = testDeepLink;
            }
        }

        // Redirect to app with error
        function redirectToAppError() {
            const errorMessage = 'Test error message';
            const deepLink = `sheerapps4d://telegram-login-error?error=${encodeURIComponent(errorMessage)}`;
            console.log('Redirecting to app with error:', deepLink);
            
            window.location.href = deepLink;
        }

        // Get referral code from URL query parameters
        function getReferralCodeFromUrl() {
            const urlParams = new URLSearchParams(window.location.search);
            return urlParams.get('referral_code');
        }

        // Auto-extract data when page loads
        window.addEventListener('load', function() {
            console.log('Page loaded, extracting Telegram data...');
            extractAndProcess();
            
            // Add debug info
            const debugDiv = document.getElementById('debugInfo');
            debugDiv.innerHTML = `
                <div class="data-item"><strong>Current URL:</strong> ${window.location.href}</div>
                <div class="data-item"><strong>URL Fragment:</strong> ${window.location.hash || 'None'}</div>
                <div class="data-item"><strong>Referral Code:</strong> ${getReferralCodeFromUrl() || 'None'}</div>
                <div class="data-item"><strong>User Agent:</strong> ${navigator.userAgent}</div>
            `;
        });
    </script>
</body>
</html>

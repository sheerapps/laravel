<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telegram OAuth Callback Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .data-item { margin: 10px 0; }
        .label { font-weight: bold; color: #333; }
        .value { color: #666; font-family: monospace; }
        .error { color: #d32f2f; }
        .success { color: #388e3c; }
        .redirect-btn { 
            background: #0088cc; 
            color: white; 
            padding: 10px 20px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
        }
        .redirect-btn:hover { background: #006699; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Telegram OAuth Callback Test</h1>
        
        <div class="section">
            <h2>📊 Request Data Received</h2>
            <div class="data-item">
                <span class="label">Method:</span>
                <span class="value">{{ request()->method() }}</span>
            </div>
            <div class="data-item">
                <span class="label">URL:</span>
                <span class="value">{{ request()->fullUrl() }}</span>
            </div>
            <div class="data-item">
                <span class="label">IP Address:</span>
                <span class="value">{{ request()->ip() }}</span>
            </div>
            <div class="data-item">
                <span class="label">User Agent:</span>
                <span class="value">{{ request()->userAgent() }}</span>
            </div>
        </div>

        <div class="section">
            <h2>🔍 Query Parameters</h2>
            @if(count(request()->query()) > 0)
                @foreach(request()->query() as $key => $value)
                    <div class="data-item">
                        <span class="label">{{ $key }}:</span>
                        <span class="value">{{ $value }}</span>
                    </div>
                @endforeach
            @else
                <div class="data-item">
                    <span class="value">No query parameters received</span>
                </div>
            @endif
        </div>

        <div class="section">
            <h2>📝 POST Data</h2>
            @if(count(request()->post()) > 0)
                @foreach(request()->post() as $key => $value)
                    <div class="data-item">
                        <span class="label">{{ $key }}:</span>
                        <span class="value">{{ $value }}</span>
                    </div>
                @endforeach
            @else
                <div class="data-item">
                    <span class="value">No POST data received</span>
                </div>
            @endif
        </div>

        <div class="section">
            <h2>📱 Redirect to App</h2>
            <p>Click the button below to test redirecting back to your React Native app:</p>
            
            <button id="successBtn" class="redirect-btn">
                🚀 Redirect to App (Success)
            </button>
            
            <button id="errorBtn" class="redirect-btn" style="background: #f44336; margin-left: 10px;">
                ❌ Redirect to App (Error)
            </button>
        </div>

        <div class="section">
            <h2>🔧 Debug Information</h2>
            <div class="data-item">
                <span class="label">Current Time:</span>
                <span class="value">{{ now() }}</span>
            </div>
            <div class="data-item">
                <span class="label">Session ID:</span>
                <span class="value">{{ session()->getId() }}</span>
            </div>
        </div>
    </div>

    <script>
        console.log('Script section loaded');
        
        // Define functions first before they're used
        function redirectToApp() {
            console.log('redirectToApp function called');
            try {
                // Test successful login redirect
                const testData = {
                    username: 'Test User',
                    avatar: 'https://via.placeholder.com/100x100?text=User',
                    status: 'active',
                    token: 'test_token_' + Date.now(),
                    user_id: Math.floor(Math.random() * 1000),
                    referrer_id: null,
                    referral_count: 0
                };
                
                const queryString = new URLSearchParams(testData).toString();
                const redirectUrl = `sheerapps4d://telegram-login-success?${queryString}`;
                
                console.log('Redirecting to:', redirectUrl);
                window.location.href = redirectUrl;
            } catch (error) {
                console.error('Error in redirectToApp:', error);
                alert('Error redirecting to app: ' + error.message);
            }
        }
        
        function redirectToAppError() {
            console.log('redirectToAppError function called');
            try {
                // Test error redirect with simple message
                const errorUrl = 'sheerapps4d://telegram-login-error?error=Login failed';
                
                console.log('Redirecting to error:', errorUrl);
                console.log('Current window.location:', window.location.href);
                
                // Try different approaches
                console.log('Attempting redirect...');
                
                // Method 1: Direct assignment
                window.location.href = errorUrl;
                
                // Method 2: If first method doesn't work, try setTimeout
                setTimeout(() => {
                    console.log('Fallback redirect attempt...');
                    window.location.href = errorUrl;
                }, 100);
                
            } catch (error) {
                console.error('Error in redirectToAppError:', error);
                alert('Error redirecting to app: ' + error.message);
            }
        }
        
        // Log all data for debugging
        console.log('Telegram OAuth Callback Data:', {
            query: @json(request()->query()),
            post: @json(request()->post()),
            headers: @json(request()->headers->all())
        });
        
        // Add event listeners after DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, functions are ready');
            
            // Test if functions are accessible
            if (typeof redirectToApp === 'function') {
                console.log('✅ redirectToApp function is available');
            } else {
                console.error('❌ redirectToApp function is NOT available');
            }
            
            if (typeof redirectToAppError === 'function') {
                console.log('✅ redirectToAppError function is available');
            } else {
                console.error('❌ redirectToAppError function is NOT available');
            }
            
            // Also test global scope
            console.log('Global scope test:');
            console.log('- window.redirectToApp:', typeof window.redirectToApp);
            console.log('- window.redirectToAppError:', typeof window.redirectToAppError);

            // Add event listeners to buttons
            document.getElementById('successBtn').addEventListener('click', redirectToApp);
            document.getElementById('errorBtn').addEventListener('click', redirectToAppError);
        });
        
        // Make functions globally accessible
        window.redirectToApp = redirectToApp;
        window.redirectToAppError = redirectToAppError;
        
        console.log('Functions defined and made global');
    </script>
</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? '4D SheerApps' }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: #f9f9f9;
            padding: 20px;
            font-size: 16px;
            line-height: 1.6;
        }
        h1, h2 {
            color: #ff6600; /* Orange 4D SheerApps color */
        }
        a {
            color: #007bff;
        }
        .container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0px 3px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
<div class="container">
    @yield('content')
</div>
</body>
</html>

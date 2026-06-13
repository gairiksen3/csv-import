<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            width: 100%;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .container {
                max-width: 500px;
            }
            .card-body {
                padding: 2rem !important;
            }
        }

        @media (max-width: 576px) {
            .container {
                max-width: 100%;
            }
            .card-body {
                padding: 1.5rem !important;
            }
        }

        .card {
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2) !important;
            border-radius: 12px;
        }
    </style>
</head>
<body>
    @yield('content')
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

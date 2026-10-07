<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CPD Portal | Authentication</title>
    
    <!-- Google Fonts & Bootstrap Icons -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="icon" type="image/png" href="{{ asset('images/icons/pitc.png') }}">
    <link href="{{ asset('css/loader.css') }}" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">

    <style>
        body {
            background-color: #F8FAFC !important;
        }

        .login-screen {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .login-card {
            width: 100%;
            max-width: 440px;
            background: #FFFFFF !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 18px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.08), 0 8px 10px -6px rgba(0, 0, 0, 0.04) !important;
            overflow: hidden;
        }

        .login-header {
            text-align: center;
            padding: 38px 30px 20px;
        }

        .login-body {
            padding: 0 32px 38px;
        }

        .login-icon-box {
            width: 58px;
            height: 58px;
            border-radius: 16px;
            background: var(--primary-gradient);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            color: #ffffff;
            margin-bottom: 18px;
            box-shadow: var(--primary-glow);
        }

        .input-group-text {
            background: #F8FAFC;
            border: 1px solid var(--border-default);
            border-right: none;
            color: var(--text-muted);
            border-radius: var(--radius-sm) 0 0 var(--radius-sm);
        }

        .input-group .form-control {
            border-left: none;
            border-radius: 0 var(--radius-sm) var(--radius-sm) 0;
        }

        .input-group:focus-within .input-group-text {
            border-color: var(--primary);
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div id="preloader">
        <div class="loader"></div>
    </div>

    <div class="login-screen">
        <div class="login-card">
            <div class="login-header">
                <div class="login-icon-box">
                    <i class="bi bi-cpu-fill"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">CPD Database Portal</h3>
                <p class="text-muted small mb-0">Sign in to access consumer analytics and database</p>
            </div>

            <div class="login-body">
                @if ($errors->any())
                    <div class="alert alert-danger mb-4 d-flex align-items-start gap-2" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-6 mt-1 flex-shrink-0"></i>
                        <ul class="mb-0 ps-2 small">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('loggedIn') }}">
                    @csrf

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-envelope-fill"></i>
                            </span>
                            <input type="email" class="form-control" id="email" name="email" 
                                   placeholder="admin@example.com" value="{{ old('email') }}" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text">
                                <i class="bi bi-lock-fill"></i>
                            </span>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Enter your password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fs-6">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Sign In to Portal
                    </button>
                </form>

                <div class="text-center mt-4 pt-3 border-top" style="border-color: var(--border-subtle) !important;">
                    <span class="text-muted small">Protected by Enterprise Authentication & RBAC</span>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script>
        $(window).on('load', function() {
            setTimeout(function() {
                $('#preloader').fadeOut(250, function() {
                    $(this).remove();
                });
            }, 100);
        });
    </script>
</body>
</html>

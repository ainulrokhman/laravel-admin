<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Laravel Admin</title>

    <!-- Google Fonts: Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Vite Assets -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f8fafc;
            overflow-x: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 480px;
            padding: 20px;
        }

        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .glass-card:hover {
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 20px 40px rgba(99, 102, 241, 0.1);
        }

        .brand-icon {
            background: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%);
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            color: white;
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
            margin-bottom: 20px;
        }

        .form-control {
            background: rgba(15, 23, 42, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: #f8fafc;
            border-radius: 10px;
            padding: 12px 16px;
            transition: all 0.25s ease;
        }

        .form-control:focus {
            background: rgba(15, 23, 42, 0.8);
            border-color: #6366f1;
            color: #ffffff;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.15);
        }

        .form-label {
            font-weight: 500;
            font-size: 0.9rem;
            color: #cbd5e1;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.25s ease;
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #4f46e5 0%, #4338ca 100%);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
        }

        .quick-login-btn {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
            font-size: 0.85rem;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .quick-login-btn:hover {
            background: rgba(99, 102, 241, 0.15);
            border-color: rgba(99, 102, 241, 0.4);
            color: #ffffff;
            transform: scale(1.02);
        }

        .invalid-feedback {
            color: #f87171;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="glass-card p-4 p-md-5">
        <div class="text-center">
            <div class="brand-icon">
                <i class="bi bi-cpu-fill"></i>
            </div>
            <h3 class="fw-bold mb-1">Welcome Back</h3>
            <p class="text-muted small mb-4">Log in to manage access control</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success bg-success bg-opacity-15 text-success border-0 rounded-3 mb-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('login') }}" method="POST" id="loginForm">
            @csrf
            
            <!-- Email Input -->
            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" 
                       class="form-control @error('email') is-invalid @enderror" 
                       id="email" 
                       name="email" 
                       value="{{ old('email') }}" 
                       placeholder="name@example.com" 
                       required 
                       autocomplete="email" 
                       autofocus>
                @error('email')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Password Input -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label mb-0">Password</label>
                </div>
                <input type="password" 
                       class="form-control @error('password') is-invalid @enderror" 
                       id="password" 
                       name="password" 
                       placeholder="••••••••" 
                       required 
                       autocomplete="current-password">
                @error('password')
                    <div class="invalid-feedback">
                        {{ $message }}
                    </div>
                @enderror
            </div>

            <!-- Remember Me -->
            <div class="mb-4 form-check d-flex align-items-center">
                <input type="checkbox" class="form-check-input" id="remember" name="remember" style="cursor: pointer;">
                <label class="form-check-label ms-2 text-muted small" for="remember" style="cursor: pointer; user-select: none;">
                    Remember my session
                </label>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary w-100 mb-4 py-2">
                Sign In <i class="bi bi-arrow-right-short ms-1"></i>
            </button>
        </form>

        <div class="border-top border-secondary border-opacity-25 pt-4">
            <h6 class="text-uppercase text-center text-muted small fw-bold mb-3" style="letter-spacing: 0.5px;">
                Quick Testing Accounts
            </h6>
            <div class="d-flex flex-column gap-2">
                <button type="button" class="quick-login-btn text-start d-flex justify-content-between align-items-center" onclick="quickLogin('superadmin@example.com', 'password')">
                    <span><strong>Super Admin</strong> (Full Access)</span>
                    <i class="bi bi-arrow-right fs-7 text-primary"></i>
                </button>
                <button type="button" class="quick-login-btn text-start d-flex justify-content-between align-items-center" onclick="quickLogin('admin@example.com', 'password')">
                    <span><strong>Admin</strong> (Manage Users & Roles)</span>
                    <i class="bi bi-arrow-right fs-7 text-success"></i>
                </button>
                <button type="button" class="quick-login-btn text-start d-flex justify-content-between align-items-center" onclick="quickLogin('user@example.com', 'password')">
                    <span><strong>Regular User</strong> (Dashboard Only)</span>
                    <i class="bi bi-arrow-right fs-7 text-info"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function quickLogin(email, password) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = password;
        
        // Visual feedback before submit
        const form = document.getElementById('loginForm');
        const submitBtn = form.querySelector('button[type="submit"]');
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Logging in...';
        submitBtn.disabled = true;

        setTimeout(() => {
            form.submit();
        }, 300);
    }
</script>

</body>
</html>

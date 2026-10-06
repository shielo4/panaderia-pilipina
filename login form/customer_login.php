<?php
session_start();
require_once __DIR__ . '/../app_config.php';
if (isset($_SESSION['role']) && $_SESSION['role'] === 'customer') {
    header('Location: ../customer%20dashboard/customer_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panaderia Pilipina | Customer Login</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #fff7ed, #fed7aa, #f59e0b);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-box {
            background: #fffdf9;
            width: min(390px, 100%);
            padding: 30px 25px;
            border-radius: 18px;
            box-shadow: 0 14px 30px rgba(146, 64, 14, 0.2);
            border: 2px solid #f59e0b;
        }
        .logo {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, #fbbf24, #f59e0b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 12px;
            font-size: 1.8rem;
        }
        .brand {
            margin-top: 0;
            text-align: center;
            color: #7c2d12;
            font-size: clamp(1.5rem, 4vw, 1.9rem);
            margin-bottom: 4px;
        }
        .subtitle {
            text-align: center;
            margin: 0 0 20px;
            color: #9a4d12;
            font-size: 0.95rem;
            font-weight: 600;
        }
        .field {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #7c2d12;
            font-weight: 600;
        }
        input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #fbbf24;
            box-sizing: border-box;
            font-size: 14px;
            background: #fffdf7;
        }
        .password-input-wrap { position: relative; }
        .password-input-wrap input { padding-right: 46px; }
        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: linear-gradient(135deg, #d97706, #b45309);
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            font-weight: 700;
            margin-top: 4px;
        }
        button:hover {
            background: linear-gradient(135deg, #b45309, #92400e);
        }
        button.password-visibility {
            position: absolute;
            top: 50%;
            right: 5px;
            display: grid;
            place-items: center;
            width: 36px;
            height: 36px;
            margin: 0;
            padding: 7px;
            transform: translateY(-50%);
            border: 0;
            border-radius: 6px;
            background: transparent;
            color: #7c2d12;
        }
        button.password-visibility:hover { background: #fff1d6; }
        .password-visibility svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .password-visibility svg[hidden] { display: none; }
        .error {
            background: #fff7ed;
            color: #c2410c;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            border: 1px solid #fdba74;
        }
        .success {
            background: #ecfdf5;
            color: #166534;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-size: 14px;
            border: 1px solid #bbf7d0;
        }
        .link {
            display: block;
            margin-top: 14px;
            text-align: center;
            color: #a16207;
            text-decoration: none;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="login-box">
        <div class="logo">🥖</div>
        <h2 class="brand">Panaderia Pilipina</h2>
        <p class="subtitle">Customer Access</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="error"><?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="success"><?php echo htmlspecialchars($_GET['success']); ?></div>
        <?php endif; ?>

        <form action="../auth.php" method="POST" autocomplete="off">
            <input type="hidden" name="role" value="customer">
            <div class="field">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" autocomplete="off" required>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <div class="password-input-wrap">
                    <input type="password" id="password" name="password" autocomplete="off" required>
                    <button class="password-visibility" type="button" id="toggle_password" aria-label="Show password" title="Show password">
                        <svg id="eye_open" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                        <svg id="eye_closed" viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18"></path><path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a14.8 14.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.3 3.8-.8"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg>
                    </button>
                </div>
            </div>
            <button type="submit" name="login" value="1">Login as Customer</button>
        </form>

        <?php if (appEnv('ENABLE_DEMO_ACCOUNTS', '0') === '1'): ?>
            <p class="link" style="margin-top: 14px; font-size: 0.82rem; color: #9a4d12;">Demo Customer: customer@gmail.com / customer123</p>
        <?php endif; ?>
        <a class="link" href="signup.php">Sign Up</a>
        <a class="link" href="forgot_password.php?role=customer">Forgot Password?</a>
        <a class="link" href="index.php">Back to Home</a>
    </div>
    <script>
        const passwordInput = document.getElementById('password');
        const passwordToggle = document.getElementById('toggle_password');
        passwordToggle.addEventListener('click', () => {
            const isVisible = passwordInput.type === 'password';
            passwordInput.type = isVisible ? 'text' : 'password';
            passwordToggle.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
            passwordToggle.title = isVisible ? 'Hide password' : 'Show password';
            document.getElementById('eye_open').hidden = isVisible;
            document.getElementById('eye_closed').hidden = !isVisible;
        });
    </script>
</body>
</html>

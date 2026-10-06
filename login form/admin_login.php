<?php
session_start();

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: ../../admin%20dashboard/admin_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Panaderia Pilipina</title>
    <style>
        * { box-sizing: border-box; }
        body { display: grid; min-height: 100vh; margin: 0; padding: 20px; place-items: center; background: linear-gradient(135deg, #fff7ed, #fed7aa, #f59e0b); font-family: Arial, sans-serif; }
        .login-box { width: min(390px, 100%); padding: 30px 25px; border: 2px solid #f59e0b; border-radius: 18px; background: #fffdf9; box-shadow: 0 14px 30px rgba(146, 64, 14, .2); }
        .logo { display: grid; width: 64px; height: 64px; margin: 0 auto 12px; place-items: center; border-radius: 50%; background: linear-gradient(135deg, #fbbf24, #f59e0b); font-size: 1.8rem; }
        h1 { margin: 0 0 5px; color: #7c2d12; text-align: center; font-size: 1.8rem; }
        .subtitle { margin: 0 0 22px; color: #9a4d12; text-align: center; font-weight: 600; }
        .field { margin-bottom: 16px; }
        label { display: block; margin-bottom: 6px; color: #7c2d12; font-weight: 600; }
        input { width: 100%; padding: 11px 12px; border: 1px solid #fbbf24; border-radius: 8px; background: #fffdf7; font-size: 15px; }
        input:focus { outline: 3px solid rgba(217, 119, 6, .2); border-color: #b45309; }
        button { width: 100%; margin-top: 4px; padding: 12px; border: 0; border-radius: 10px; background: linear-gradient(135deg, #b45309, #92400e); color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; }
        .error { margin-bottom: 16px; padding: 10px; border: 1px solid #fdba74; border-radius: 8px; background: #fff7ed; color: #c2410c; font-size: 14px; }
        .link { display: block; margin-top: 15px; color: #a16207; text-align: center; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
    <main class="login-box">
        <div class="logo" aria-hidden="true">🥖</div>
        <h1>Panaderia Pilipina</h1>
        <p class="subtitle">Administrator Access</p>
        <?php if (isset($_GET['error'])): ?>
            <p class="error" role="alert"><?php echo htmlspecialchars($_GET['error'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>
        <?php require_once __DIR__ . '/../app_config.php'; ?>
        <form action="../auth.php" method="POST" autocomplete="off">
            <input type="hidden" name="role" value="admin">
            <div class="field">
                <label for="email">Email Address</label>
                <input id="email" type="email" name="email" autocomplete="off" required autofocus>
            </div>
            <div class="field">
                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="off" required>
            </div>
            <button type="submit" name="login" value="1">Login as Admin</button>
        </form>
        <?php if (!appAdminCredentialsReady()): ?>
            <p class="error" role="alert">Admin login is not configured on this server. Set the ADMIN_EMAIL and ADMIN_PASSWORD environment variables.</p>
        <?php endif; ?>
        <a class="link" href="index.php">Back to Home</a>
    </main>
</body>
</html>
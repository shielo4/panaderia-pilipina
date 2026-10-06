<?php
session_start();

if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: ../dashboard%20admin/admin_dashboard.php');
    exit();
}
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
    <title>Panaderia Pilipina</title>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: radial-gradient(circle at top, #fff7d6 0%, #f7d98a 30%, #f2b94b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .portal {
            background: rgba(255, 255, 255, 0.96);
            padding: 35px 28px 30px;
            border-radius: 22px;
            box-shadow: 0 18px 40px rgba(120, 69, 18, 0.22);
            width: min(460px, 100%);
            text-align: center;
            border: 2px solid #d97706;
        }
        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #facc15, #f59e0b);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 2.2rem;
            box-shadow: 0 10px 22px rgba(217, 119, 6, 0.25);
        }
        .brand {
            color: #7c2d12;
            font-size: clamp(2rem, 4vw, 2.5rem);
            margin: 0;
            letter-spacing: 0.04em;
        }
        .tagline {
            color: #78350f;
            margin: 10px 0 25px;
            font-size: 0.98rem;
        }
        .option {
            display: block;
            margin: 15px 0;
            padding: 18px 18px;
            border-radius: 14px;
            text-decoration: none;
            font-size: 1.05rem;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.05em;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            box-shadow: 0 12px 18px rgba(146, 64, 14, 0.15);
        }
        .option:hover {
            transform: translateY(-2px);
            opacity: 0.98;
        }
        .admin {
            background: linear-gradient(135deg, #b45309, #92400e);
        }
        .customer {
            background: linear-gradient(135deg, #d97706, #b45309);
        }
    </style>
</head>
<body>
    <main class="portal">
        <div class="logo">🥖</div>
        <h1 class="brand">Panaderia Pilipina</h1>
        <p class="tagline">Fresh bread, warm service, and trusted daily bakery care.</p>
        <a class="option admin" href="admin_login.php">ADMIN LOGIN</a>
        <a class="option customer" href="customer_login.php">CUSTOMER LOGIN</a>
    </main>
</body>
</html>

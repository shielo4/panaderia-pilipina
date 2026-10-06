<?php
require_once __DIR__ . '/../account_store.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[A-Za-zÀ-ÿ .\'-]{2,80}$/u', $fullName)) {
        $error = 'Please enter a valid full name.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a valid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $accounts = loadCustomerAccounts();
        $emailTaken = false;
        foreach ($accounts as $account) {
            if (strtolower($account['email'] ?? '') === $email) {
                $emailTaken = true;
                break;
            }
        }

        if ($emailTaken) {
            $error = 'That email is already registered.';
        } else {
            $accounts[] = [
                'full_name' => $fullName,
                'email' => $email,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'created_at' => date(DATE_ATOM),
            ];
            if (saveCustomerAccounts($accounts)) {
                header('Location: customer_login.php?success=Account+created.+Please+log+in.');
                exit();
            }
            $error = 'Could not save your account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panaderia Pilipina | Sign Up</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; padding: 20px; display: grid; place-items: center; font-family: Arial, sans-serif; background: linear-gradient(135deg, #fff7ed, #fed7aa, #f59e0b); }
        .signup-box { width: min(410px, 100%); padding: 30px 25px; border: 2px solid #f59e0b; border-radius: 18px; background: #fffdf9; box-shadow: 0 14px 30px rgba(146, 64, 14, .2); }
        h1 { margin: 0 0 6px; text-align: center; color: #7c2d12; font-size: 1.7rem; }
        .subtitle { margin: 0 0 22px; text-align: center; color: #9a4d12; }
        .field { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; color: #7c2d12; font-weight: 700; }
        input { width: 100%; padding: 11px 12px; border: 1px solid #fbbf24; border-radius: 8px; background: #fffdf7; font-size: 14px; }
        button { width: 100%; padding: 12px; border: 0; border-radius: 10px; background: linear-gradient(135deg, #d97706, #b45309); color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; }
        .message { margin-bottom: 15px; padding: 10px; border-radius: 8px; background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; font-size: 14px; }
        .link { display: block; margin-top: 14px; text-align: center; color: #a16207; text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <main class="signup-box">
        <h1>Create Customer Account</h1>
        <p class="subtitle">Join Panaderia Pilipina</p>
        <?php if ($error !== ''): ?><div class="message"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
        <form method="POST">
            <div class="field"><label for="full_name">Full Name</label><input id="full_name" name="full_name" required maxlength="80" autocomplete="name"></div>
            <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" required autocomplete="email"></div>
            <div class="field"><label for="password">Password</label><input id="password" type="password" name="password" required minlength="8" autocomplete="new-password"></div>
            <div class="field"><label for="confirm_password">Confirm password</label><input id="confirm_password" type="password" name="confirm_password" required minlength="8" autocomplete="new-password"></div>
            <button type="submit">Create Account</button>
        </form>
        <a class="link" href="customer_login.php">Back to Customer Login</a>
    </main>
</body>
</html>

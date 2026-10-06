<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SERVER['REQUEST_METHOD'] = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$_POST = $_POST ?? [];
require_once __DIR__ . '/../account_store.php';

$role = ($_GET['role'] ?? $_POST['role'] ?? 'customer') === 'admin' ? 'admin' : 'customer';
$message = '';

if (!function_exists('generateOtp')) {
    function generateOtp(): string
    {
        return (string)random_int(100000, 999999);
    }
}

if (!function_exists('smtpReadResponse')) {
    function smtpReadResponse($socket): string
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) >= 3 && $line[3] === ' ') {
                break;
            }
        }

        return trim($response);
    }
}

if (!function_exists('smtpConfigValue')) {
    function smtpConfigValue(string $key, string $default = ''): string
    {
        $environmentValue = getenv($key);
        if ($environmentValue !== false && $environmentValue !== '') {
            return $environmentValue;
        }

        $configPath = dirname(__DIR__, 3) . '/.env';
        if (!is_file($configPath) || !is_readable($configPath)) {
            return $default;
        }

        foreach (file($configPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$configKey, $value] = explode('=', $line, 2);
            if (trim($configKey) !== $key) {
                continue;
            }

            $value = trim($value);
            if (strlen($value) >= 2 && (($value[0] === '"' && $value[-1] === '"') || ($value[0] === "'" && $value[-1] === "'"))) {
                $value = substr($value, 1, -1);
            }

            return $value;
        }

        return $default;
    }
}

if (!function_exists('sendOtpEmail')) {
    function sendOtpEmail(string $email, string $otp): bool
    {
        $username = smtpConfigValue('GMAIL_SMTP_USER');
        $password = preg_replace('/\s+/', '', smtpConfigValue('GMAIL_SMTP_PASS'));
        $fromEmail = $username;
        $fromName = smtpConfigValue('GMAIL_SMTP_FROM_NAME', 'Panaderia Pilipina');

        if ($username === '' || $password === '') {
            return false;
        }

        $socket = fsockopen('ssl://smtp.gmail.com', 465, $errno, $errstr, 30);
        if ($socket === false) {
            return false;
        }

        stream_set_timeout($socket, 30);
        $banner = smtpReadResponse($socket);
        if (!str_starts_with($banner, '220')) {
            fclose($socket);
            return false;
        }

        $commands = [
            ["EHLO localhost\r\n", '250'],
            ["AUTH LOGIN\r\n", '334'],
            [base64_encode($username) . "\r\n", '334'],
            [base64_encode($password) . "\r\n", '235'],
            ["MAIL FROM:<{$fromEmail}>\r\n", '250'],
            ["RCPT TO:<{$email}>\r\n", '250'],
            ["DATA\r\n", '354'],
        ];

        foreach ($commands as [$command, $expectedCode]) {
            if (fwrite($socket, $command) === false || !str_starts_with(smtpReadResponse($socket), $expectedCode)) {
                fclose($socket);
                return false;
            }
        }

        $message = "Subject: Panaderia Pilipina Password Reset OTP\r\n";
        $message .= "From: \"{$fromName}\" <{$fromEmail}>\r\n";
        $message .= "To: <{$email}>\r\n";
        $message .= "MIME-Version: 1.0\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n";
        $message .= "Your OTP is: {$otp}\r\n\r\nThis code is valid for 10 minutes.\r\n\r\nPanaderia Pilipina\r\n";
        $message = preg_replace('/(^|\r\n)\./', '$1..', $message) . ".\r\n";

        if (fwrite($socket, $message) === false || !str_starts_with(smtpReadResponse($socket), '250')) {
            fclose($socket);
            return false;
        }

        fwrite($socket, "QUIT\r\n");
        smtpReadResponse($socket);
        fclose($socket);
        return true;
    }
}

if (!function_exists('findCustomerByIdentity')) {
    function findCustomerByIdentity(array $accounts, string $fullName, string $email): ?array
    {
        foreach ($accounts as $account) {
            $storedFullName = trim((string)($account['full_name'] ?? ''));
            $storedUsername = trim((string)($account['username'] ?? ''));
            $nameMatches = $storedFullName !== '' && strtolower($storedFullName) === strtolower($fullName);
            $legacyUsernameMatches = $storedUsername !== '' && strtolower($storedUsername) === strtolower($fullName);
            $emailMatches = strtolower((string)($account['email'] ?? '')) === strtolower($email);

            if (($nameMatches || $legacyUsernameMatches) && $emailMatches) {
                return $account;
            }
        }

        return null;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $role === 'customer') {
    $action = $_POST['action'] ?? 'send_otp';
    $fullName = trim($_POST['full_name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));

    if ($action === 'send_otp') {
        if ($fullName === '' || $email === '') {
            $message = 'Please enter your full name and registered email.';
        } else {
            $accounts = loadCustomerAccounts();
            $customer = findCustomerByIdentity($accounts, $fullName, $email);

            if ($customer === null) {
                $message = 'We could not find a matching customer account with that full name and email.';
            } else {
                $otp = generateOtp();
                $_SESSION['reset_email'] = $email;
                $_SESSION['reset_full_name'] = $fullName;
                $_SESSION['reset_otp'] = $otp;
                $_SESSION['reset_expires'] = time() + 600;

                $sent = sendOtpEmail($email, $otp);
                if ($sent) {
                    $message = 'An OTP has been sent to your registered email. Enter it below to continue.';
                } else {
                    unset($_SESSION['reset_email'], $_SESSION['reset_full_name'], $_SESSION['reset_otp'], $_SESSION['reset_expires']);
                    $message = 'OTP email could not be sent. Configure GMAIL_SMTP_USER and GMAIL_SMTP_PASS with your Gmail app password to enable real email OTP.';
                }
            }
        }
    } elseif ($action === 'verify_reset') {
        $otp = trim((string)($_POST['otp'] ?? ''));
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $sessionEmail = $_SESSION['reset_email'] ?? '';
        $sessionOtp = $_SESSION['reset_otp'] ?? '';
        $expiresAt = (int)($_SESSION['reset_expires'] ?? 0);

        if ($sessionEmail === '' || $sessionOtp === '' || $expiresAt < time()) {
            $_SESSION['reset_email'] = '';
            $_SESSION['reset_otp'] = '';
            $_SESSION['reset_expires'] = 0;
            $message = 'Your OTP has expired. Please request a new one.';
        } elseif ($otp !== $sessionOtp) {
            $message = 'The OTP you entered is incorrect.';
        } elseif (strlen($newPassword) < 8) {
            $message = 'Password must be at least 8 characters.';
        } elseif ($newPassword !== $confirmPassword) {
            $message = 'Passwords do not match.';
        } else {
            $accounts = loadCustomerAccounts();
            $accountIndex = null;
            foreach ($accounts as $index => $account) {
                if (strtolower((string)($account['email'] ?? '')) === $sessionEmail) {
                    $accountIndex = $index;
                    break;
                }
            }

            if ($accountIndex === null) {
                $message = 'We could not find the customer account linked to this email.';
            } else {
                $accounts[$accountIndex]['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
                if (!saveCustomerAccounts($accounts)) {
                    $message = 'Could not update the password. Please try again.';
                } else {
                    $_SESSION['reset_email'] = '';
                    $_SESSION['reset_full_name'] = '';
                    $_SESSION['reset_otp'] = '';
                    $_SESSION['reset_expires'] = 0;
                    header('Location: customer_login.php?success=Password+reset.+Please+log+in+with+your+new+password.');
                    exit();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panaderia Pilipina | Forgot Password</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; padding: 20px; display: grid; place-items: center; font-family: Arial, sans-serif; background: linear-gradient(135deg, #fff7ed, #fed7aa, #f59e0b); }
        .recovery-box { width: min(430px, 100%); padding: 30px 25px; border: 2px solid #f59e0b; border-radius: 18px; background: #fffdf9; box-shadow: 0 14px 30px rgba(146, 64, 14, .2); }
        h1 { margin: 0 0 8px; text-align: center; color: #7c2d12; font-size: 1.7rem; }
        .subtitle, .notice { color: #78716c; line-height: 1.5; font-size: .9rem; }
        .subtitle { margin: 0 0 20px; text-align: center; }
        .notice { margin: 0 0 18px; padding: 11px; border: 1px solid #fdba74; border-radius: 8px; background: #fff7ed; color: #9a3412; }
        .field { margin-bottom: 15px; }
        label { display: block; margin-bottom: 6px; color: #7c2d12; font-weight: 700; }
        input { width: 100%; padding: 11px 12px; border: 1px solid #fbbf24; border-radius: 8px; background: #fffdf7; font-size: 14px; }
        .password-input-wrap { position: relative; }
        .password-input-wrap input { padding-right: 46px; }
        button { width: 100%; padding: 12px; border: 0; border-radius: 10px; background: linear-gradient(135deg, #d97706, #b45309); color: #fff; font-size: 16px; font-weight: 700; cursor: pointer; }
        .password-visibility { position: absolute; top: 50%; right: 5px; display: grid; place-items: center; width: 36px; height: 36px; margin: 0; padding: 7px; transform: translateY(-50%); border: 0; border-radius: 6px; background: transparent; color: #7c2d12; }
        .password-visibility:hover { background: #fff1d6; }
        .password-visibility svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round; }
        .password-visibility svg[hidden] { display: none; }
        .message { margin-bottom: 15px; padding: 10px; border-radius: 8px; background: #ecfdf5; color: #166534; border: 1px solid #bbf7d0; font-size: 14px; }
        .link { display: block; margin-top: 14px; text-align: center; color: #a16207; text-decoration: none; font-weight: 600; }
        .muted { font-size: 0.8rem; color: #7c2d12; margin-top: 8px; }
        .demo-code { font-weight: 700; letter-spacing: 0.12em; }
    </style>
</head>
<body>
    <main class="recovery-box">
        <h1>Forgot Password?</h1>
        <p class="subtitle">Recover access to your Panaderia Pilipina account.</p>
        <?php if ($role === 'admin'): ?>
            <p class="notice">Admin password recovery is restricted. Contact the site administrator to restore admin access.</p>
            <a class="link" href="admin_login.php">Back to Admin Login</a>
        <?php else: ?>
            <?php if ($message !== ''): ?><div class="message"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

            <?php if (empty($_SESSION['reset_email'])): ?>
                <p class="notice">Enter your full name and the email linked to your account. We will send a one-time password to that email.</p>
                <form method="POST">
                    <input type="hidden" name="role" value="customer">
                    <input type="hidden" name="action" value="send_otp">
                    <div class="field"><label for="full_name">Full Name</label><input id="full_name" name="full_name" required autocomplete="name"></div>
                    <div class="field"><label for="email">Registered email</label><input id="email" type="email" name="email" required autocomplete="email"></div>
                    <button type="submit">Send OTP</button>
                </form>
            <?php else: ?>
                <p class="notice">OTP sent to <?php echo htmlspecialchars($_SESSION['reset_email']); ?></p>
                <form method="POST">
                    <input type="hidden" name="role" value="customer">
                    <input type="hidden" name="action" value="verify_reset">
                    <div class="field"><label for="otp">OTP Code</label><input id="otp" name="otp" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code"></div>
                    <div class="field"><label for="new_password">New password</label><div class="password-input-wrap"><input id="new_password" type="password" name="new_password" required minlength="8" autocomplete="new-password"><button class="password-visibility" type="button" data-target="new_password" aria-label="Show password" title="Show password"><svg id="eye_open_new" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg><svg id="eye_closed_new" viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18"></path><path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a14.8 14.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.3 3.8-.8"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg></button></div></div>
                    <div class="field"><label for="confirm_password">Confirm new password</label><div class="password-input-wrap"><input id="confirm_password" type="password" name="confirm_password" required minlength="8" autocomplete="new-password"><button class="password-visibility" type="button" data-target="confirm_password" aria-label="Show password" title="Show password"><svg id="eye_open_confirm" viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg><svg id="eye_closed_confirm" viewBox="0 0 24 24" aria-hidden="true" hidden><path d="m3 3 18 18"></path><path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a14.8 14.8 0 0 1-3.1 3.8M6.2 6.2C3.5 8 2 12 2 12s3.6 7 10 7c1.4 0 2.7-.3 3.8-.8"></path><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"></path></svg></button></div></div>
                    <button type="submit">Reset Password</button>
                </form>
                <a class="link" href="forgot_password.php?role=customer">Request a new OTP</a>
            <?php endif; ?>
            <a class="link" href="customer_login.php">Back to Customer Login</a>
        <?php endif; ?>
    </main>
    <script>
        document.querySelectorAll('.password-visibility').forEach((toggleButton) => {
            toggleButton.addEventListener('click', () => {
                const input = document.getElementById(toggleButton.dataset.target);
                if (!input) {
                    return;
                }
                const isVisible = input.type === 'password';
                input.type = isVisible ? 'text' : 'password';
                toggleButton.setAttribute('aria-label', isVisible ? 'Hide password' : 'Show password');
                toggleButton.title = isVisible ? 'Hide password' : 'Show password';
                const eyeOpen = toggleButton.querySelector('svg:not([hidden])');
                const eyeClosed = toggleButton.querySelector('svg[hidden]');
                if (eyeOpen) {
                    eyeOpen.hidden = isVisible;
                }
                if (eyeClosed) {
                    eyeClosed.hidden = !isVisible;
                }
            });
        });
    </script>
</body>
</html>

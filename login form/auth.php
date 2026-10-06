<?php
session_start();
require_once __DIR__ . '/../account_store.php';
require_once __DIR__ . '/../app_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['login'])) {
    $_SESSION = [];
    session_regenerate_id(true);
    header('Location: index.php');
    exit();
}

require_once __DIR__ . '/../login_guard.php';
if (!loginGuardAllowsAttempt('login')) {
    header('Location: index.php?error=' . rawurlencode('Too many attempts. Please try again in a few minutes.'));
    exit();
}

$role = strtolower(trim((string)($_POST['role'] ?? '')));
$email = strtolower(trim((string)($_POST['email'] ?? '')));
$password = (string)($_POST['password'] ?? '');

if ($role === 'admin' && appAdminLoginSucceeds($email, $password)) {
    loginGuardReset('login');
    $_SESSION = [];
    session_regenerate_id(true);
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
    header('Location: ../admin%20dashboard/admin_dashboard.php');
    exit();
}

if ($role === 'customer') {
    foreach (loadCustomerAccounts() as $account) {
        if (strtolower((string)($account['email'] ?? '')) === $email && password_verify($password, $account['password'] ?? '')) {
            loginGuardReset('login');
            $_SESSION = [];
            session_regenerate_id(true);
            $_SESSION['role'] = 'customer';
            $_SESSION['username'] = trim((string)($account['full_name'] ?? $account['username'] ?? $email));
            header('Location: ../customer%20dashboard/customer_dashboard.php');
            exit();
        }
    }
}

// Demo customer logins stay off unless explicitly enabled.
if (appEnv('ENABLE_DEMO_ACCOUNTS', '0') === '1') {
    $demoCustomerEmails = ['customer@gmail.com', 'customer@panaderia.com'];
    if ($role === 'customer' && in_array($email, $demoCustomerEmails, true) && $password === 'customer123') {
        loginGuardReset('login');
        $_SESSION = [];
        session_regenerate_id(true);
        $_SESSION['role'] = 'customer';
        $_SESSION['username'] = 'customer';
        header('Location: ../customer%20dashboard/customer_dashboard.php');
        exit();
    }
}

$loginPage = ($role === 'admin') ? 'admin_login.php' : 'customer_login.php';

// The failed-attempt counter is recorded AFTER clearing the session above,
// otherwise $_SESSION = [] would wipe the throttle state we just wrote.
loginGuardRecordFailure('login');
header('Location: ' . $loginPage . '?error=Invalid+email+or+password');
exit();

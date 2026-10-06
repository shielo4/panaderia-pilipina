<?php
require_once __DIR__ . '/app_config.php';

function customerAccountsPath(): string
{
    return appDataDir() . '/bread_accounts.json';
}

/**
 * Seed accounts are only created on a brand-new install. They are never
 * required for sign-in: the admin signs in with ADMIN_EMAIL/ADMIN_PASSWORD,
 * and customers register via signup.php.
 */
function defaultCustomerAccounts(): array
{
    if (appEnv('ENABLE_DEMO_ACCOUNTS', '0') !== '1') {
        return [];
    }

    return [
        [
            'full_name' => 'Customer Demo',
            'username' => 'customer',
            'email' => 'customer@gmail.com',
            'password' => password_hash('customer123', PASSWORD_DEFAULT),
            'created_at' => date(DATE_ATOM),
        ],
    ];
}

function loadCustomerAccounts(): array
{
    $path = customerAccountsPath();
    if (!is_file($path)) {
        $accounts = defaultCustomerAccounts();
        saveCustomerAccounts($accounts);
        return $accounts;
    }

    $contents = file_get_contents($path);
    $accounts = json_decode($contents === false ? '' : $contents, true);
    if (!is_array($accounts) || $accounts === []) {
        $accounts = defaultCustomerAccounts();
        saveCustomerAccounts($accounts);
        return $accounts;
    }

    return $accounts;
}

function saveCustomerAccounts(array $accounts): bool
{
    $json = json_encode(array_values($accounts), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    return $json !== false && file_put_contents(customerAccountsPath(), $json, LOCK_EX) !== false;
}

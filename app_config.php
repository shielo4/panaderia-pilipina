<?php
/**
 * Central configuration for Panaderia Pilipina.
 *
 * Admin credentials and SMTP settings are NO LONGER hardcoded in the app.
 * They are read from (in priority order):
 *   1. Real environment variables - how Render/Docker inject secrets.
 *   2. A local .env file (never committed; blocked from HTTP by .htaccess).
 *
 * Keeping secrets out of the source tree means the repository can be made
 * public without handing out the admin dashboard.
 */

// __DIR__ is the repository root. Keep the legacy Project1 path for older
// local checkouts.
function appEnvFiles(): array
{
    return [
        __DIR__ . '/.env',
        __DIR__ . '/Project1/.env',
    ];
}

function appEnvFilePath(): ?string
{
    foreach (appEnvFiles() as $path) {
        if (is_file($path) && is_readable($path)) {
            return $path;
        }
    }
    return null;
}

/**
 * Read a configuration value, preferring the real environment.
 */
function appEnv(string $key, string $default = ''): string
{
    $fromEnvironment = getenv($key);
    if ($fromEnvironment !== false && $fromEnvironment !== '') {
        return $fromEnvironment;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return (string)$_ENV[$key];
    }

    $path = appEnvFilePath();
    if ($path === null) {
        return $default;
    }

    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
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

/**
 * Directory for writable JSON data. On Render this points at a persistent
 * disk so accounts and orders survive restarts and deploys.
 */
function appDataDir(): string
{
    $configured = appEnv('DATA_DIR', '');
    if ($configured !== '') {
        if (!is_dir($configured) && !mkdir($configured, 0775, true) && !is_dir($configured)) {
            throw new RuntimeException('Unable to create configured data directory: ' . $configured);
        }
        if (!is_writable($configured)) {
            throw new RuntimeException('Configured data directory is not writable: ' . $configured);
        }

        return rtrim($configured, '/\\');
    }

    return __DIR__;
}

function appAdminEmail(): string
{
    return strtolower(trim(appEnv('ADMIN_EMAIL', '')));
}

function appAdminPassword(): string
{
    return appEnv('ADMIN_PASSWORD', '');
}

/**
 * Admin access is disabled entirely unless strong credentials are configured,
 * so a public deployment can never fall back to a well-known demo password.
 */
function appAdminCredentialsReady(): bool
{
    $email = appAdminEmail();
    $password = appAdminPassword();

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && strlen($password) >= 8
        && !in_array($password, ['Babyshielo', 'customer123', 'password', 'admin'], true);
}

/**
 * Constant-time admin credential check.
 */
function appAdminLoginSucceeds(string $email, string $password): bool
{
    if (!appAdminCredentialsReady()) {
        return false;
    }

    $expectedEmail = appAdminEmail();
    $expectedPassword = appAdminPassword();

    return hash_equals($expectedEmail, strtolower(trim($email)))
        && hash_equals($expectedPassword, $password);
}
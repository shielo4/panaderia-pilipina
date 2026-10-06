<?php
/**
 * Central configuration for Panaderia Pilipina.
 *
 * Admin credentials and SMTP settings are NO LONGER hardcoded in the app.
 * They are read from (in priority order):
 *   1. Real environment variables - how Render/Docker inject secrets.
 *   2. A local .env file (never committed; blocked from HTTP by .htaccess/router.php).
 *
 * Keeping secrets out of the source tree means the repository can be made
 * public without handing out the admin dashboard.
 */

// Candidate .env locations: repo root (preferred, outside the document root)
// and Project1/ (legacy location, kept working for local development).
function appEnvFiles(): array
{
    // __DIR__ = Project1/customer dashboard/dashboard admin
    $projectRoot = dirname(__DIR__, 2);   // Project1
    return [
        dirname($projectRoot) . '/.env',  // repo root - preferred
        $projectRoot . '/.env',           // legacy local dev path
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
    $projectRoot = dirname(__DIR__, 2);
    $configured = appEnv('DATA_DIR', '');
    if ($configured !== '') {
        if (!is_dir($configured)) {
            @mkdir($configured, 0775, true);
        }
        if (is_dir($configured) && is_writable($configured)) {
            return rtrim($configured, '/\\');
        }
    }
    return $projectRoot;
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
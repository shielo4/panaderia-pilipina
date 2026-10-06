<?php
/**
 * Very small per-session login throttle.
 *
 * Not a replacement for a WAF, but it stops casual online password guessing
 * against a publicly deployed instance.
 */

const LOGIN_GUARD_MAX_FAILURES = 8;
const LOGIN_GUARD_WINDOW_SECONDS = 900;

function loginGuardStateKey(string $bucket): string
{
    return 'login_guard_' . $bucket;
}

function loginGuardAllowsAttempt(string $bucket): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return true;
    }

    $key = loginGuardStateKey($bucket);
    $state = $_SESSION[$key] ?? ['failures' => 0, 'first' => time()];
    $window = LOGIN_GUARD_WINDOW_SECONDS;

    if ((int)($state['failures'] ?? 0) >= LOGIN_GUARD_MAX_FAILURES
        && (time() - (int)($state['first'] ?? time())) < $window) {
        return false;
    }

    return true;
}

function loginGuardRecordFailure(string $bucket): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return;
    }

    $key = loginGuardStateKey($bucket);
    $state = $_SESSION[$key] ?? ['failures' => 0, 'first' => time()];

    if ((time() - (int)($state['first'] ?? time())) >= LOGIN_GUARD_WINDOW_SECONDS) {
        $state = ['failures' => 0, 'first' => time()];
    }

    $state['failures'] = (int)($state['failures'] ?? 0) + 1;
    $_SESSION[$key] = $state;
}

function loginGuardReset(string $bucket): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        unset($_SESSION[loginGuardStateKey($bucket)]);
    }
}
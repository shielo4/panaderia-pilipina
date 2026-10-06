<?php
require_once __DIR__ . '/app_config.php';

function customerOrdersPath(): string
{
    return appDataDir() . '/bread_orders.json';
}

function loadAllCustomerOrders(): array
{
    $handle = @fopen(customerOrdersPath(), 'rb');
    if ($handle === false) {
        return [];
    }

    if (!flock($handle, LOCK_SH)) {
        fclose($handle);
        return [];
    }

    $contents = stream_get_contents($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    $orders = json_decode($contents === false ? '' : $contents, true);
    if (!is_array($orders)) {
        return [];
    }

    return array_values(array_filter($orders, 'is_array'));
}

function loadCustomerOrders(string $customerEmail): array
{
    return array_values(array_filter(loadAllCustomerOrders(), static function ($order) use ($customerEmail): bool {
        return is_array($order) && strtolower((string)($order['customerEmail'] ?? '')) === strtolower($customerEmail);
    }));
}

function saveCustomerOrder(array $order): bool
{
    $handle = @fopen(customerOrdersPath(), 'c+');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        return false;
    }

    rewind($handle);
    $contents = stream_get_contents($handle);
    $orders = json_decode($contents === false ? '' : $contents, true);
    if ($contents !== '' && !is_array($orders)) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }
    $orders = is_array($orders) ? $orders : [];

    foreach ($orders as $existingOrder) {
        if (($existingOrder['id'] ?? null) === ($order['id'] ?? null)) {
            flock($handle, LOCK_UN);
            fclose($handle);
            return true;
        }
    }

    array_unshift($orders, $order);
    $json = json_encode($orders, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return false;
    }

    rewind($handle);
    $written = fwrite($handle, $json);
    $saved = $written === strlen($json) && ftruncate($handle, strlen($json)) && fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);

    return $saved;
}
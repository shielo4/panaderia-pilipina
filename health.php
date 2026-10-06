<?php
/**
 * Lightweight liveness endpoint used by the Docker HEALTHCHECK and by Render's
 * healthCheckPath. It reveals nothing about the app beyond "it is alive".
 */
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');

echo json_encode([
    'status' => 'ok',
    'app' => 'Panaderia Pilipina',
]);
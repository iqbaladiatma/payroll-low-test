<?php
// Simple network connectivity test
header('Content-Type: application/json');

$response = [
    'status' => 'success',
    'message' => 'BullsCorp server is accessible!',
    'server_info' => [
        'ip' => $_SERVER['SERVER_ADDR'],
        'port' => $_SERVER['SERVER_PORT'],
        'time' => date('Y-m-d H:i:s'),
        'client_ip' => $_SERVER['REMOTE_ADDR']
    ],
    'test_endpoints' => [
        'login' => '../public/login.php',
        'register' => '../public/register.php',
        'server_info' => 'server_info.php'
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);
?>
<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/app.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
echo json_encode(['token' => csrf_token()], JSON_THROW_ON_ERROR);

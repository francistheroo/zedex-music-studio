<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/release_schema.php';
require_admin();
$pdo = database_connection();
ensure_release_schema($pdo);
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(404); exit; }
$stmt = $pdo->prepare('SELECT mime_type,image_data FROM release_artwork WHERE release_id=:id LIMIT 1');
$stmt->execute(['id'=>$id]);
$image = $stmt->fetch();
if (!$image || !in_array($image['mime_type'], ['image/jpeg','image/png','image/webp'], true)) { http_response_code(404); exit; }
$data = base64_decode((string)$image['image_data'], true);
if (!is_string($data) || $data === '') { http_response_code(404); exit; }
header('Content-Type: ' . $image['mime_type']);
header('Content-Length: ' . strlen($data));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, max-age=3600');
echo $data;

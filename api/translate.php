<?php
/**
 * Tabya Translation API Endpoint
 * Returns JSON response for asynchronous in-page translation
 */
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/translator.php';

$raw = file_get_contents('php://input');
$body = json_decode($raw, true) ?? [];

$text = $_POST['text'] ?? $_POST['english_text'] ?? $body['text'] ?? $body['english_text'] ?? $_GET['text'] ?? '';
$text = trim($text);

$direction = $_POST['direction'] ?? $body['direction'] ?? $_GET['direction'] ?? 'en_to_yen';
if (!in_array($direction, ['en_to_yen', 'yen_to_en'])) {
    $direction = 'en_to_yen';
}

if ($text === '') {
    echo json_encode([
        'success' => false,
        'error' => 'Teks masukan tidak boleh kosong.'
    ]);
    exit();
}

$res = translate_text($text, $direction);

echo json_encode([
    'success' => true,
    'data' => $res
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);


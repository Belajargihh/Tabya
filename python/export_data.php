<?php
// Export MySQL dataset to python/dataset.json
$db = new mysqli('localhost', 'root', '', 'tabya_db');
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$result = $db->query("SELECT id, category, english_text, yenbuba_text, indonesia_text, pronunciation, notes FROM datasets ORDER BY id ASC");
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

$jsonPath = __DIR__ . '/dataset.json';
file_put_contents($jsonPath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Successfully exported " . count($data) . " dataset entries to " . $jsonPath;


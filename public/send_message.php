<?php
$chat_id = $_POST['chat_id'] ?? '';
$message = $_POST['message'] ?? '';
$sessionName = $_POST['sessionName'] ?? 'session1';

$url = 'http://localhost:3000/send-message';
$data = json_encode([
  'sessionName' => $sessionName,
  'to' => $chat_id,
  'message' => $message
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$response = curl_exec($ch);
curl_close($ch);

header("Location: chat.php?chat_id=" . urlencode($chat_id) . "&session=" . urlencode($sessionName));
exit;
?>

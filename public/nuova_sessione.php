<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session = $_POST['session_name'];
    $name = $_POST['display_name'];

    $db = new SQLite3('messages.db');
    $stmt = $db->prepare('INSERT OR REPLACE INTO session_users(session_name, display_name) VALUES (?, ?)');
    $stmt->bindValue(1, $session, SQLITE3_TEXT);
    $stmt->bindValue(2, $name, SQLITE3_TEXT);
    $stmt->execute();

    // Avvia sessione tramite API
    $postData = json_encode(["sessionName" => $session]);
    $opts = ['http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json",
        'content' => $postData
    ]];
    $context = stream_context_create($opts);
    @file_get_contents("http://localhost:3000/start-session", false, $context);

    header("Location: index.php?sessionName=$session");
    exit;
}
?>
<h2>Nuova sessione</h2>
<form method="post">
    Numero (sessione): <input type="text" name="session_name" required><br>
    Nome da associare: <input type="text" name="display_name" required><br>
    <button type="submit">Avvia sessione</button>
</form>
<a href="index.php">Torna indietro</a>

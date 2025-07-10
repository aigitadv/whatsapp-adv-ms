<?php include 'header.php'; ?>
<?php
$session = $_GET['sessionName'] ?? '';
if (!$session) {
    echo "<h3>Benvenuto! Seleziona una sessione dal menu.</h3>";
    exit;
}

// Verifica stato sessione
$qr = @file_get_contents("http://localhost:3000/qr?sessionName=" . urlencode($session));
if ($qr && strpos($qr, 'qr') !== false) {
    $qr = json_decode($qr, true)['qr'];
    echo "<h3>Scansiona il QR per avviare la sessione <b>$session</b></h3>";
    echo "<img src='https://api.qrserver.com/v1/create-qr-code/?data=" . urlencode($qr) . "&size=200x200'>";
    exit;
}

// Altrimenti mostra chat
echo "<h3>Chat per sessione: <b>$session</b></h3>";
?>
<div id="chat-list"></div>
<div id="chat-box" style="border:1px solid #ccc;height:200px;overflow:auto;margin-top:10px"></div>
<form onsubmit="sendMessage(); return false;">
    <textarea id="message" rows="2" cols="50"></textarea><br>
    <button>Invia</button>
</form>
<script>
let session = "<?= $session ?>";

function loadChat() {
    fetch("chat-history.php?sessionName=" + session + "&chatId=preview").then(r => r.json()).then(data => {
        const box = document.getElementById('chat-box');
        box.innerHTML = data.map(d => `<div><b>${d.sender}:</b> ${d.message}</div>`).join('');
        box.scrollTop = box.scrollHeight;
    });
}
function sendMessage() {
    const msg = document.getElementById("message").value;
    fetch("send_message.php", {
        method: "POST",
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ sessionName: session, to: "preview", message: msg })
    }).then(() => {
        document.getElementById("message").value = "";
        loadChat();
    });
}
setInterval(loadChat, 2000);
</script>

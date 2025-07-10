<?php
$chat_id = $_GET['chat_id'] ?? '';
$sessionName = $_GET['session'] ?? 'session1';

// Avvio sessione (puoi anche commentare se hai già avviato manualmente)
$startSessionUrl = 'http://localhost:3000/start-session';
$data = json_encode(['sessionName' => $sessionName]);

$ch = curl_init($startSessionUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
$response = curl_exec($ch);
curl_close($ch);
?>
<!DOCTYPE html>
<html>
<head>
  <title>Chat con <?= htmlspecialchars($chat_id) ?> (<?= htmlspecialchars($sessionName) ?>)</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    #chat-box {
      height: 300px;
      overflow-y: auto;
      background: #f8f9fa;
      border: 1px solid #ddd;
      padding: 10px;
      font-family: monospace;
      white-space: pre-wrap;
    }
  </style>
</head>
<body class="container my-4">
  <h1>Chat con <?= htmlspecialchars($chat_id) ?></h1>
  <h5>Sessione: <?= htmlspecialchars($sessionName) ?></h5>
  <div id="chat-box">
    <p class="text-muted">Caricamento messaggi...</p>
  </div>

  <form id="msg-form" class="d-flex gap-2 mt-3">
    <input type="text" id="message-input" placeholder="Scrivi un messaggio" class="form-control" required autocomplete="off" />
    <button type="submit" id="send-btn" class="btn btn-primary">Invia</button>
  </form>

  <script>
    const chatId = <?= json_encode($chat_id) ?>;
    const sessionName = <?= json_encode($sessionName) ?>;
    let lastLogLength = 0;

    function fetchMessages() {
      $.getJSON('http://localhost:3000/chat-history', { chatId }, function(data) {
        if (data.length > lastLogLength) {
          let html = '';
          data.reverse().forEach(msg => {
      const sender = msg.sender === 'me' ? 'Tu' : msg.sender;
      const time = new Date(msg.timestamp > 1e12 ? msg.timestamp : msg.timestamp * 1000).toLocaleString();
      html += `<p><strong>${sender}:</strong> ${msg.message} <small class="text-muted">${time}</small></p>`;
    });
          $('#chat-box').html(html);
          $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
          lastLogLength = data.length;
        }
      });
    }

    function checkSessionReady() {
      return $.getJSON('http://localhost:3000/session-status', { sessionName })
        .then(statusData => statusData.status === 'ready')
        .catch(() => false);
    }

    async function updateSendButton() {
      const ready = await checkSessionReady();
      $('#send-btn').prop('disabled', !ready);
      if (!ready) {
        $('#send-btn').text('Sessione non pronta...');
      } else {
        $('#send-btn').text('Invia');
      }
    }

    $(document).ready(function() {
      fetchMessages();
      updateSendButton();

      setInterval(() => {
        fetchMessages();
        updateSendButton();
      }, 3000);

      $('#msg-form').submit(async function(e) {
        e.preventDefault();
        const message = $('#message-input').val().trim();
        if (!message) return;

        const ready = await checkSessionReady();
        if (!ready) {
          alert('Sessione non pronta, riprova tra qualche secondo.');
          return;
        }

        $('#send-btn').prop('disabled', true);
        $.ajax({
          url: 'send_message.php',
          method: 'POST',
          data: { chat_id: chatId, message: message, sessionName: sessionName },
          success: function() {
            $('#message-input').val('');
            fetchMessages();
          },
          error: function() {
            alert('Errore invio messaggio');
          },
          complete: function() {
            updateSendButton();
          }
        });
      });
    });
  </script>
</body>
</html>

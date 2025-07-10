<!DOCTYPE html>
<html>
<head>
  <title>Avvio Sessione WhatsApp</title>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <style>
    #qr img { width: 250px; height: 250px; }
    #log { border: 1px solid #ccc; height: 300px; overflow-y: scroll; padding: 10px; background: #f9f9f9; font-family: monospace; white-space: pre-wrap; }
  </style>
</head>
<body>
  <h1>Avvio sessione WhatsApp</h1>
  <button id="start">Avvia Sessione</button>
  <div id="qr"></div>
  <h3>Log sessione</h3>
  <div id="log"></div>

  <script>
    const sessionName = 'sessionNomasvello';
    let lastLogLength = 0;

    $('#start').click(function() {
      $('#log').append('Avvio sessione...\n');
      $.ajax({
        url: 'http://localhost:3000/start-session',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({ sessionName }),
        success: function(res) {
          $('#log').append(res + '\n');
          startPolling();
        },
        error: function(err) {
          $('#log').append('Errore avvio: ' + err.responseText + '\n');
        }
      });
    });

    function updateLogs(logs) {
      if (logs.length > lastLogLength) {
        for (let i = lastLogLength; i < logs.length; i++) {
          $('#log').append(logs[i] + '\n');
        }
        $('#log').scrollTop($('#log')[0].scrollHeight);
        lastLogLength = logs.length;
      }
    }

    let pollingInterval;
    function startPolling() {
      pollingInterval = setInterval(() => {
        $.getJSON('http://localhost:3000/session-status', { sessionName }, function(statusData) {
          if (statusData.status === 'ready') {
            clearInterval(pollingInterval); // ferma polling QR
            $('#qr').html('<p>Sessione pronta!</p>');
          } else {
            $.getJSON('http://localhost:3000/qr', { sessionName }, function(data) {
              if (data.qr) {
                const qrImage = '<img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' + encodeURIComponent(data.qr) + '"/>';
                $('#qr').html(qrImage);
              }
            });
          }

          $.getJSON('http://localhost:3000/logs', { sessionName }, function(data) {
            updateLogs(data.logs);
          });
        });
      }, 10000); // polling ogni 10 secondi
    }
  </script>
</body>
</html>

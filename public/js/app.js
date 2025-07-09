$(function() {
  const api = 'http://localhost:3000';
  let currentSession = null, currentChat = null;

  window.quill = new Quill('#editor-container', {
    modules: { toolbar: ['bold','italic','emoji'], 'emoji-toolbar': true}, theme: 'snow'
  });

  function loadSessions() {
    $.getJSON(`${api}/session-status`, data => {
      $('#session-list').empty();
      data.forEach(s => {
        $('#session-list').append(`<li class="list-group-item session-item" data-session="${s.session_name}">${s.session_name} (${s.status})</li>`);
      });
    });
  }
  loadSessions();

  $('#new-session-btn').click(() => {
    const name = prompt('Nome sessione:'); if(!name) return;
    $.post(`${api}/start-session`, { sessionName: name }, () => loadSessions());
  });

  $(document).on('click', '.session-item', function() {
    currentSession = $(this).data('session');
    $('#debug-log').text('');
    pollQRandLogs();
    loadChatList();
  });

  let qrInterval, logInterval;
  function pollQRandLogs() {
    clearInterval(qrInterval); clearInterval(logInterval);
    qrInterval = setInterval(() => {
      $.getJSON(`${api}/qr`, { sessionName: currentSession }, d => {
        const img = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(d.qr)}"/>`;
        $('#qr-container').html(img);
      });
    }, 5000);
    logInterval = setInterval(() => {
      $.getJSON(`${api}/logs`, { sessionName: currentSession }, d => $('#debug-log').text(d.join('\n')));
    }, 2000);
  }

  function loadChatList() {
    $.getJSON(`${api}/chat-history`, { sessionName: currentSession, chatId: '' }, data => {
      const chats = [...new Set(data.map(m=>m.chat_id))];
      $('#sidebar').empty();
      chats.forEach(c => $('#sidebar').append(`<div class="chat-item p-2 border-bottom" data-chat="${c}">${c}</div>`));
    });
  }

  $(document).on('click', '.chat-item', function() {
    currentChat = $(this).data('chat');
    loadHistory();
  });

  function loadHistory() {
    $('#messages').empty();
    $.getJSON(`${api}/chat-history`, { sessionName: currentSession, chatId: currentChat }, data => {
      data.forEach(m => $('#messages').append(`<div><strong>${m.sender}</strong>: ${m.message}</div>`));
      $('#messages').scrollTop($('#messages')[0].scrollHeight);
    });
  }

  $('#send-btn').click(async () => {
    const text = quill.root.innerHTML;
    $.post(`${api}/send-message`, { sessionName: currentSession, to: currentChat, message: text }, loadHistory);
    quill.setContents([]);
  });
});

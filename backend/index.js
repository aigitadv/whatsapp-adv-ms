const { Client, LocalAuth, MessageMedia } = require('whatsapp-web.js');
const express = require('express');
const cors = require('cors');
const sqlite3 = require('sqlite3').verbose();
const path = require('path');

const app = express();
app.use(cors());
app.use(express.json());
app.use(express.static(path.join(__dirname, '../public')));

const dbFile = process.env.DB_PATH || './messages.db';
const db = new sqlite3.Database(dbFile);
db.serialize(() => {
  db.run(`CREATE TABLE IF NOT EXISTS sessions (
    session_name TEXT PRIMARY KEY,
    status TEXT,
    last_updated INTEGER
  )`);
  db.run(`CREATE TABLE IF NOT EXISTS messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    session_name TEXT,
    chat_id TEXT,
    sender TEXT,
    message TEXT,
    media_url TEXT,
    timestamp INTEGER
  )`);
});

const sessions = {}, sessionsQr = {}, sessionsLog = {};

function logSession(name, msg) {
  if (!sessionsLog[name]) sessionsLog[name] = [];
  sessionsLog[name].push(msg);
  if (sessionsLog[name].length > 200) sessionsLog[name].shift();
}
function updateSessionStatus(name, status) {
  const t = Date.now();
  db.run(\`INSERT INTO sessions(session_name,status,last_updated)
          VALUES(?,?,?)
          ON CONFLICT(session_name) DO UPDATE SET status=excluded.status,last_updated=excluded.last_updated\`,
    [name, status, t]);
}

function startSession(name) {
  const client = new Client({
    authStrategy: new LocalAuth({ clientId: name }),
    puppeteer: { headless: true }
  });

  client.on('qr', qr => {
    sessionsQr[name] = qr;
    logSession(name, '📲 QR generato');
    updateSessionStatus(name, 'qr');
  });
  client.on('ready', () => { logSession(name, '✅ Pronto'); updateSessionStatus(name, 'ready'); });
  client.on('auth_failure', () => { logSession(name, '❌ Auth failure'); updateSessionStatus(name, 'auth_failure'); });
  client.on('disconnected', reason => {
    logSession(name, \`⚠️ Disconnesso: \${reason}\`);
    updateSessionStatus(name, 'disconnected');
    delete sessions[name];
  });

  client.on('message_create', async msg => {
    const chat = await msg.getChat();
    const chatId = msg.fromMe ? msg.to : msg.from;
    const sender = msg.fromMe ? 'me' : msg.from;
    db.run(\`INSERT INTO messages(session_name,chat_id,sender,message,timestamp)
            VALUES(?,?,?,?,?)\`, [name, chatId, sender, msg.body, msg.timestamp]);

    if (!msg.fromMe) {
      await chat.sendState('typing');
      const delay = Math.min(Math.max(msg.body.length * 80, 1000), 8000);
      await new Promise(r => setTimeout(r, delay));
      await chat.sendState('paused');
      await msg.reply(\`Hai scritto: \${msg.body}\`);
      logSession(name, \`↩️ Risposta inviata dopo \${delay}ms\`);
    }
  });

  client.initialize();
  sessions[name] = client;
}

app.post('/start-session', (req, res) => {
  const { sessionName } = req.body;
  if (!sessionName) return res.status(400).send('Nome sessione richiesto');
  if (sessions[sessionName]) return res.status(400).send('Sessione già attiva');
  startSession(sessionName);
  res.send(\`Sessione \${sessionName} avviata\`);
});
app.get('/session-status', (req, res) => {
  db.all(\`SELECT session_name,status,last_updated FROM sessions\`, [], (e, rows) => {
    if (e) return res.status(500).send(e.message);
    res.json(rows);
  });
});
app.get('/qr', (req, res) => {
  const { sessionName } = req.query;
  const qr = sessionsQr[sessionName];
  if (!qr) return res.status(404).send('QR non disponibile');
  res.json({ qr });
});
app.get('/logs', (req, res) => {
  const { sessionName } = req.query;
  res.json(sessionsLog[sessionName] || []);
});
app.get('/chat-history', (req, res) => {
  const { sessionName, chatId } = req.query;
  db.all(\`SELECT * FROM messages WHERE session_name=? AND chat_id=? ORDER BY timestamp ASC\`, [sessionName, chatId], (e, rows) => {
    if (e) return res.status(500).send(e.message);
    res.json(rows);
  });
});
app.post('/send-message', async (req, res) => {
  const { sessionName, to, message, mediaUrl } = req.body;
  const client = sessions[sessionName]; if (!client) return res.status(400).send('Sessione mancante');
  try {
    if (mediaUrl) {
      const media = await MessageMedia.fromUrl(mediaUrl);
      await client.sendMessage(to, media);
    } else {
      await client.sendMessage(to, message);
    }
    res.send('Inviato');
  } catch (e) { res.status(500).send(e.message); }
});

app.get('/status', (req, res) => {
  res.send('OK');
});

app.listen(3000, () => console.log('Server avviato su http://localhost:3000'));

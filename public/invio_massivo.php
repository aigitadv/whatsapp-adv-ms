<?php
// bulk_send.php

// Disabilita buffering per output in tempo reale
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
while (ob_get_level()) ob_end_flush();
ob_implicit_flush(1);

header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Invio Bulk</title></head><body>';
echo '<h1>Invio messaggi in corso...</h1><pre>';

// --- Array di TEST (solo due numeri) ---
$testContacts = [
  //  '393404236309' => 'Desia',
    '393665475879' => 'Marco',
]; 

// --- Array di PRODUZIONE (lista completa) ---
$prodContacts = [

];

// Scegli quale array usare:
 $contacts = $testContacts;  // per TEST
//$contacts = $prodContacts; // per PRODUZIONE

// Messaggio multilinea con emoji e placeholder <NAME>
$messageTemplate = <<<MSG
Ti stai trovando bene con il laser e stai pensando di trattare una nuova zona?
È il momento perfetto per iniziare! 🌞

Solo per te, la prima seduta della nuova zona è scontata del 50%! ✨
E in più… ricevi in omaggio una splendida borsa mare in edizione limitata, perfetta per accompagnarti tutta l’estate! 👜🌊

Non perdere questa occasione: inizia ora e continua a prenderti cura di te!
Contattaci per scegliere la zona e prenotare il tuo appuntamento 💬💆‍♀️

Ti aspetto al centro Nomasvello di via Duchessa Jolanda 14! 💛
MSG;

// Configurazione API
$apiUrl      = 'http://localhost:3000/send-message';
$sessionName = 'sessionNomasvello';

foreach ($contacts as $num => $name) {
    $to      = $num . '@c.us';
    $message = str_replace('<NAME>', $name, $messageTemplate);

    $payload = json_encode([
        'sessionName' => $sessionName,
        'to'          => $to,
        'message'     => $message
    ], JSON_UNESCAPED_UNICODE);

    // cURL request
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_TIMEOUT        => 10,
    ]);
    $result = curl_exec($ch);
    $err    = curl_error($ch);
    curl_close($ch);

    // Output in tempo reale
    if ($err) {
        echo "[❌] Errore invio a $num ($name): $err\n";
    } else {
        echo "[✅] Messaggio inviato a $num ($name)\n";
    }

    // Pausa casuale tra 1 e 3 secondi
    $delay = rand(1, 3);
    sleep($delay);
}

echo '</pre><p>🎉 Invio bulk completato!</p></body></html>';

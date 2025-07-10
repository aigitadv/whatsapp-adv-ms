<?php
$db = new SQLite3('messages.db');
$sessions = $db->query("SELECT * FROM session_users");
?>
<div style='background:#eee;padding:10px'>
    <a href='index.php'>🏠 Home</a> |
    <span>Sessioni:</span>
    <select onchange="if(this.value) window.location='index.php?sessionName='+this.value">
        <option value=''>-- Seleziona --</option>
        <?php while($row = $sessions->fetchArray()): ?>
            <option value="<?= $row['session_name'] ?>"><?= $row['session_name'] ?> - <?= $row['display_name'] ?></option>
        <?php endwhile; ?>
    </select> |
    <a href='nuova_sessione.php'>➕ Nuova sessione</a>
</div>

<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/lib.php';
if ($argc < 3 || !in_array('--replace', $argv, true)) {
    fwrite(STDERR, "Usage: php restore.php backup.json --replace\n   or: php restore.php --mysql --replace\n\nThis replaces the current workspace. Keep the original private config.php.\nSet INNERCIRCLE_DATA_DIR to the private directory used by Apache.\n"); exit(1);
}
try {
    ic_requirements();
    if (!ic_installed()) throw new RuntimeException('Set INNERCIRCLE_DATA_DIR to the existing private directory containing the original config.php.');
    if ($argv[1] === '--mysql') {
        $row = ic_mysql(ic_config())->query('SELECT encrypted_payload FROM innercircle_backups ORDER BY created_at DESC, id DESC LIMIT 1')->fetch();
        if (!$row) throw new RuntimeException('No MySQL backup exists yet.');
        $encrypted = $row['encrypted_payload'];
    } else {
        if (!is_file($argv[1])) throw new RuntimeException('Backup file not found.');
        $backup = json_decode(file_get_contents($argv[1]), true, 512, JSON_THROW_ON_ERROR);
        if (($backup['format'] ?? '') !== 'innercircle-php-v1') throw new RuntimeException('This is not an innercircle PHP backup. Preview PostgreSQL backups use a separate format.');
        $encrypted = $backup['encryptedPayload'] ?? '';
    }
    $snapshot = ic_decrypt($encrypted);
    if (empty($snapshot['workspace']['passwordHash']) || !isset($snapshot['contacts'], $snapshot['accounts'], $snapshot['journals'])) throw new RuntimeException('The backup is incomplete. Nothing was changed.');
    foreach (['accounts', 'journals'] as $kind) foreach ($snapshot[$kind] as $record) ic_decrypt($record['encryptedData']);
    $safetyFile = ic_private_dir() . '/before-restore-' . gmdate('Ymd-His') . '.json';
    if (file_put_contents($safetyFile, json_encode(ic_backup(), JSON_THROW_ON_ERROR), LOCK_EX) === false) throw new RuntimeException('Could not create the safety backup. Nothing was changed.');
    @chmod($safetyFile, 0600);
    $db = ic_db(); $db->beginTransaction();
    try {
        $db->exec('DELETE FROM ic_records');
        foreach (['contacts' => 'contact', 'accounts' => 'account', 'journals' => 'journal'] as $plural => $kind) foreach ($snapshot[$plural] as $record) {
            if (!is_array($record) || empty($record['id'])) throw new RuntimeException('A record is invalid.');
            ic_save($kind, $record);
        }
        $snapshot['workspace']['authVersion'] = bin2hex(random_bytes(16));
        ic_set_meta($snapshot['workspace']); $db->commit();
    } catch (Throwable $error) { $db->rollBack(); throw $error; }
    file_put_contents(ic_private_dir() . '/install.lock', 'Restored and locked at ' . gmdate('c') . "\n", LOCK_EX);
    echo "Workspace restored. A safety backup is stored in the private data directory.\nAll previous owner sessions are invalidated. Unlock with the password from this backup.\n";
} catch (Throwable $error) { fwrite(STDERR, 'Restore stopped: ' . $error->getMessage() . "\n"); exit(1); }

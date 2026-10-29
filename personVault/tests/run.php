<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$tmp = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'personvault-test-' . bin2hex(random_bytes(6));
$assertions = 0;
function check(bool $ok, string $message): void {
    global $assertions;
    $assertions++;
    if (!$ok) throw new RuntimeException('FAIL: ' . $message);
    echo "PASS: {$message}\n";
}
function copy_tree(string $from, string $to): void {
    if (!is_dir($to)) mkdir($to, 0700, true);
    foreach (scandir($from) ?: [] as $item) {
        if ($item === '.' || $item === '..') continue;
        $src = $from . DIRECTORY_SEPARATOR . $item; $dst = $to . DIRECTORY_SEPARATOR . $item;
        if (is_dir($src)) copy_tree($src, $dst); else copy($src, $dst);
    }
}
try {
    if (!extension_loaded('pdo_sqlite') || !extension_loaded('openssl')) throw new RuntimeException('Tests require PDO SQLite and OpenSSL.');
    mkdir($tmp . '/app', 0700, true); mkdir($tmp . '/var', 0700, true); mkdir($tmp . '/private/data', 0700, true); mkdir($tmp . '/private/keys', 0700, true);
    copy($root . '/app/bootstrap.php', $tmp . '/app/bootstrap.php'); copy($root . '/app/migrate.php', $tmp . '/app/migrate.php');
    $keyPath = $tmp . '/private/keys/test.key'; file_put_contents($keyPath, random_bytes(32));
    $dbPath = $tmp . '/private/data/test.sqlite';
    $config = ['database'=>['driver'=>'sqlite','sqlite_path'=>$dbPath], 'encryption_key_file'=>$keyPath, 'app_name'=>'TestVault','timezone'=>'UTC'];
    file_put_contents($tmp . '/private/config.php', "<?php return " . var_export($config, true) . ";\n");
    file_put_contents($tmp . '/var/install.lock', $tmp . '/private');
    require $tmp . '/app/bootstrap.php'; require $tmp . '/app/migrate.php';
    $secret = ['title'=>'private title','body'=>'private body','password'=>'sensitive'];
    $encrypted = app_encrypt($secret);
    check($encrypted !== json_encode($secret), 'Record contents are not stored as readable JSON');
    check(app_decrypt($encrypted) === $secret, 'AES-GCM data decrypts with the matching key');
    $tampered = $encrypted; $tampered[5] = $tampered[5] === 'A' ? 'B' : 'A';
    $rejected = false; try { app_decrypt($tampered); } catch (Throwable) { $rejected = true; }
    check($rejected, 'Modified ciphertext is rejected');
    $pdo = app_db(); app_migrate($pdo); app_migrate($pdo);
    check((int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn() === 1, 'Schema migration is repeatable and does not duplicate version rows');
    $user = $pdo->prepare('INSERT INTO users(email,password_hash,role,created_at) VALUES(?,?,?,?)');
    $user->execute(['one@example.test',password_hash('long-test-password',PASSWORD_DEFAULT),'user',app_now()]); $ownerOne=(int)$pdo->lastInsertId();
    $user->execute(['two@example.test',password_hash('another-test-password',PASSWORD_DEFAULT),'user',app_now()]); $ownerTwo=(int)$pdo->lastInsertId();
    $row=$pdo->prepare('INSERT INTO records(id,owner_id,module,payload,created_at,updated_at) VALUES(?,?,?,?,?,?)');
    $row->execute([bin2hex(random_bytes(16)),$ownerOne,'journal',app_encrypt(['title'=>'one']),app_now(),app_now()]);
    $row->execute([bin2hex(random_bytes(16)),$ownerTwo,'journal',app_encrypt(['title'=>'two']),app_now(),app_now()]);
    $owned=$pdo->prepare('SELECT COUNT(*) FROM records WHERE owner_id = ? AND module = ?');$owned->execute([$ownerOne,'journal']);
    check((int)$owned->fetchColumn()===1,'Owner-scoped query returns only the selected user records');
    $verify=password_verify('long-test-password',$pdo->query("SELECT password_hash FROM users WHERE email='one@example.test'")->fetchColumn());
    check($verify,'Application login password hash verifies');
    echo "PASS: {$assertions} assertions completed.\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n"); exit(1);
} finally {
    app_db_close();
    unset($pdo, $user, $row, $owned, $adminCount);
    if (is_dir($tmp)) {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($tmp, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iterator as $entry) $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        rmdir($tmp);
    }
}

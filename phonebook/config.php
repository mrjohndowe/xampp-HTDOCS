<?php
// Copy this package to C:\xampp\htdocs\phonebook\ (or /opt/lampp/htdocs/phonebook).
declare(strict_types=1);
define('APP_NAME','My Phone Book');
$localSettingsPath = __DIR__ . '/config.local.php';
$localSettings = is_file($localSettingsPath) ? require $localSettingsPath : [];
if (!is_array($localSettings)) {
  throw new RuntimeException('config.local.php must return a database configuration array.');
}
$localSettings += [
  'dbDriver' => 'sqlite',
  'sqliteFile' => 'phonebook.sqlite',
  'mysqlHost' => '127.0.0.1',
  'mysqlPort' => '3306',
  'mysqlDb' => 'phonebook',
  'mysqlUser' => 'root',
  'mysqlPass' => '',
];
$localSettings['dbDriver'] = in_array($localSettings['dbDriver'], ['sqlite', 'mysql'], true) ? $localSettings['dbDriver'] : 'sqlite';
$localSettings['sqliteFile'] = basename((string)$localSettings['sqliteFile']);
define('DB_DRIVER', $localSettings['dbDriver']);
define('SQLITE_PATH', __DIR__ . '/database/' . $localSettings['sqliteFile']);
define('MYSQL_HOST', $localSettings['mysqlHost']);
define('MYSQL_PORT', $localSettings['mysqlPort']);
define('MYSQL_DB', $localSettings['mysqlDb']);
define('MYSQL_USER', $localSettings['mysqlUser']);
define('MYSQL_PASS', $localSettings['mysqlPass']);
define('INSTALL_LOCK', __DIR__ . '/install.lock');
function pdo(?array $configuration = null): PDO {
  $driver = $configuration['dbDriver'] ?? DB_DRIVER;
  if ($driver === 'mysql') {
    $host = $configuration['mysqlHost'] ?? MYSQL_HOST;
    $port = $configuration['mysqlPort'] ?? MYSQL_PORT;
    $database = $configuration['mysqlDb'] ?? MYSQL_DB;
    $user = $configuration['mysqlUser'] ?? MYSQL_USER;
    $password = $configuration['mysqlPass'] ?? MYSQL_PASS;
    $databasePart = $database === '' ? '' : ';dbname=' . $database;
    return new PDO('mysql:host=' . $host . ';port=' . $port . $databasePart . ';charset=utf8mb4', $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  }
  $sqlitePath = $configuration['sqlitePath'] ?? SQLITE_PATH;
  $pdo = new PDO('sqlite:' . $sqlitePath, null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
  $pdo->exec('PRAGMA foreign_keys=ON');
  return $pdo;
}
function is_installed(): bool { return file_exists(INSTALL_LOCK); }
function age_from_dob(?string $dob): ?int { if(!$dob) return null; $b=new DateTimeImmutable($dob); $t=new DateTimeImmutable('today'); return $b->diff($t)->y; }

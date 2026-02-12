#!/usr/bin/env php
<?php
/**
 * PHP-based mysqldump replacement for Alpine containers.
 *
 * MariaDB's mysqldump (the only one available on Alpine) cannot authenticate
 * to MySQL 8.4's caching_sha2_password. This script uses PHP PDO instead,
 * parsing the same CLI args that spatie/db-dumper passes to mysqldump.
 */

$host = '127.0.0.1';
$port = '3306';
$user = 'root';
$password = '';
$database = '';
$resultFile = '';
$singleTransaction = false;
$skipSsl = false;
$extraWhere = '';

// Parse arguments (compatible with spatie/db-dumper's mysqldump invocation)
$args = array_slice($argv, 1);
$positional = [];

for ($i = 0; $i < count($args); $i++) {
    $arg = $args[$i];

    if (preg_match('/^--defaults-extra-file[=]?"?(.+?)"?$/', $arg, $m)) {
        // Parse the defaults file for credentials
        $contents = file_get_contents($m[1]);
        if (preg_match("/user\s*=\s*'?([^'\n]+)/", $contents, $um)) $user = $um[1];
        if (preg_match("/password\s*=\s*'?([^'\n]+)/", $contents, $pm)) $password = $pm[1];
        if (preg_match("/host\s*=\s*'?([^'\n]+)/", $contents, $hm)) $host = $hm[1];
        if (preg_match("/port\s*=\s*'?([^'\n]+)/", $contents, $ptm)) $port = $ptm[1];
    } elseif (preg_match('/^--result-file[=]?"?(.+?)"?$/', $arg, $m)) {
        $resultFile = $m[1];
    } elseif ($arg === '--single-transaction') {
        $singleTransaction = true;
    } elseif (preg_match('/^--ssl[=]?0$/', $arg) || $arg === '--skip-ssl') {
        $skipSsl = true;
    } elseif (in_array($arg, ['--skip-comments', '--extended-insert', '--no-tablespaces',
        '--set-gtid-purged=OFF', '--quick', '--skip-lock-tables', '--add-locks',
        '--disable-keys', '--column-statistics=0'])) {
        // Silently accept common flags
    } elseif (preg_match('/^--default-auth=/', $arg)) {
        // Ignore auth plugin flag
    } elseif (!str_starts_with($arg, '-')) {
        $positional[] = $arg;
    }
}

// Last positional arg is the database name
if (!empty($positional)) {
    $database = array_pop($positional);
}

if (empty($database)) {
    fwrite(STDERR, "Error: no database specified\n");
    exit(1);
}

// Connect via PDO
$dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
];

if ($skipSsl) {
    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
}

try {
    $pdo = new PDO($dsn, $user, $password, $options);
} catch (PDOException $e) {
    fwrite(STDERR, "Connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

// Open output
$out = $resultFile ? fopen($resultFile, 'w') : STDOUT;
if (!$out) {
    fwrite(STDERR, "Cannot open result file: {$resultFile}\n");
    exit(1);
}

// Header
fwrite($out, "-- PHP-based mysqldump for Alpine/MySQL 8.4 compatibility\n");
fwrite($out, "-- Server: {$host}:{$port}  Database: {$database}\n");
fwrite($out, "-- Date: " . date('Y-m-d H:i:s') . "\n\n");
fwrite($out, "SET NAMES utf8mb4;\n");
fwrite($out, "SET FOREIGN_KEY_CHECKS = 0;\n\n");

if ($singleTransaction) {
    $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $pdo->beginTransaction();
}

// Get all tables
$tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN, 0);

foreach ($tables as $table) {
    $quotedTable = "`{$table}`";

    // CREATE TABLE statement
    $create = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch();
    fwrite($out, "DROP TABLE IF EXISTS {$quotedTable};\n");
    fwrite($out, $create['Create Table'] . ";\n\n");

    // Data — extended insert format, batched
    $countStmt = $pdo->query("SELECT COUNT(*) FROM {$quotedTable}");
    $rowCount = (int) $countStmt->fetchColumn();

    if ($rowCount === 0) continue;

    // Get column list for INSERT statement
    $columns = $pdo->query("SHOW COLUMNS FROM {$quotedTable}")->fetchAll();
    $colNames = array_map(fn($c) => "`{$c['Field']}`", $columns);
    $colList = implode(', ', $colNames);

    $stmt = $pdo->query("SELECT * FROM {$quotedTable}", PDO::FETCH_NUM);
    $batchSize = 500;
    $batch = [];

    fwrite($out, "LOCK TABLES {$quotedTable} WRITE;\n");

    while ($row = $stmt->fetch()) {
        $values = [];
        foreach ($row as $val) {
            if ($val === null) {
                $values[] = 'NULL';
            } else {
                $values[] = $pdo->quote($val);
            }
        }
        $batch[] = '(' . implode(',', $values) . ')';

        if (count($batch) >= $batchSize) {
            fwrite($out, "INSERT INTO {$quotedTable} ({$colList}) VALUES\n" . implode(",\n", $batch) . ";\n");
            $batch = [];
        }
    }

    if (!empty($batch)) {
        fwrite($out, "INSERT INTO {$quotedTable} ({$colList}) VALUES\n" . implode(",\n", $batch) . ";\n");
    }

    fwrite($out, "UNLOCK TABLES;\n\n");
}

// Dump views
$views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN, 0);
foreach ($views as $view) {
    $create = $pdo->query("SHOW CREATE VIEW `{$view}`")->fetch();
    fwrite($out, "DROP VIEW IF EXISTS `{$view}`;\n");
    fwrite($out, $create['Create View'] . ";\n\n");
}

if ($singleTransaction) {
    $pdo->commit();
}

fwrite($out, "SET FOREIGN_KEY_CHECKS = 1;\n");

if ($resultFile) {
    fclose($out);
}

exit(0);

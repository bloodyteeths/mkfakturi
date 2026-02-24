<?php
// Fix invoices stuck in PARTIALLY_PAID with due_amount <= 100 (1.00 MKD)
$db = new PDO(
    "mysql:host=" . getenv("MYSQLHOST") . ";port=" . getenv("MYSQLPORT") . ";dbname=" . getenv("MYSQLDATABASE"),
    getenv("MYSQLUSER"),
    getenv("MYSQLPASSWORD"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== Before fix ===\n";
$stmt = $db->query("SELECT id, invoice_number, total, due_amount, paid_status, status FROM invoices WHERE paid_status = 'PARTIALLY_PAID' AND due_amount <= 100");
$toFix = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($toFix as $row) {
    echo sprintf("ID:%d  #%s  total:%s  due:%s  paid_status:%s\n", $row['id'], $row['invoice_number'], $row['total'], $row['due_amount'], $row['paid_status']);
}

if (empty($toFix)) {
    echo "No invoices to fix.\n";
    exit(0);
}

echo "\n=== Fixing " . count($toFix) . " invoices ===\n";
$stmt = $db->prepare("UPDATE invoices SET due_amount = 0, base_due_amount = 0, paid_status = 'PAID', status = 'COMPLETED', overdue = 0 WHERE id = ?");
foreach ($toFix as $row) {
    $stmt->execute([$row['id']]);
    echo "Fixed invoice #" . $row['invoice_number'] . " (ID:" . $row['id'] . ")\n";
}

echo "\n=== After fix ===\n";
$stmt = $db->query("SELECT COUNT(*) as cnt FROM invoices WHERE paid_status = 'PARTIALLY_PAID'");
echo "Remaining PARTIALLY_PAID: " . $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] . "\n";

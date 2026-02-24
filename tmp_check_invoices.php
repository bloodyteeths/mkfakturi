<?php
// Check invoices stuck in PARTIALLY_PAID with 0 due_amount
$db = new PDO(
    "mysql:host=" . getenv("MYSQLHOST") . ";port=" . getenv("MYSQLPORT") . ";dbname=" . getenv("MYSQLDATABASE"),
    getenv("MYSQLUSER"),
    getenv("MYSQLPASSWORD"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== Invoices with PARTIALLY_PAID and due_amount close to 0 ===\n";
$stmt = $db->query("SELECT id, invoice_number, total, due_amount, base_due_amount, paid_status, status, currency_id FROM invoices WHERE paid_status = 'PARTIALLY_PAID' ORDER BY due_amount ASC LIMIT 20");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("ID:%d  #%s  total:%s  due:%s  base_due:%s  paid_status:%s  status:%s  currency:%s\n",
        $row['id'], $row['invoice_number'], $row['total'], $row['due_amount'], $row['base_due_amount'], $row['paid_status'], $row['status'], $row['currency_id']);
}

echo "\n=== Count of PARTIALLY_PAID invoices with due_amount = 0 ===\n";
$stmt = $db->query("SELECT COUNT(*) as cnt FROM invoices WHERE paid_status = 'PARTIALLY_PAID' AND due_amount = 0");
echo "Exact zero: " . $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] . "\n";

$stmt = $db->query("SELECT COUNT(*) as cnt FROM invoices WHERE paid_status = 'PARTIALLY_PAID' AND due_amount <= 0.01 AND due_amount >= -0.01");
echo "Near zero (within 0.01): " . $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] . "\n";

$stmt = $db->query("SELECT COUNT(*) as cnt FROM invoices WHERE paid_status = 'PARTIALLY_PAID'");
echo "Total PARTIALLY_PAID: " . $stmt->fetch(PDO::FETCH_ASSOC)['cnt'] . "\n";

echo "\n=== Payments for invoice 2026000031 ===\n";
$stmt = $db->query("SELECT i.id, i.invoice_number, i.total, i.due_amount FROM invoices WHERE invoice_number LIKE '%2026000031%' OR invoice_number LIKE '%2026000030%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("Invoice #%s (ID:%d): total=%s due=%s\n", $row['invoice_number'], $row['id'], $row['total'], $row['due_amount']);
    $pstmt = $db->prepare("SELECT id, amount, payment_date, payment_number FROM payments WHERE invoice_id = ?");
    $pstmt->execute([$row['id']]);
    while ($p = $pstmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("  Payment #%s: amount=%s date=%s\n", $p['payment_number'], $p['amount'], $p['payment_date']);
    }
}

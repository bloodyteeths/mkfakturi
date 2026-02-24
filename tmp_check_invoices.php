<?php
// Check invoices stuck in PARTIALLY_PAID with 0 due_amount
$db = new PDO(
    "mysql:host=" . getenv("MYSQLHOST") . ";port=" . getenv("MYSQLPORT") . ";dbname=" . getenv("MYSQLDATABASE"),
    getenv("MYSQLUSER"),
    getenv("MYSQLPASSWORD"),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

echo "=== Invoices with PARTIALLY_PAID ===\n";
$stmt = $db->query("SELECT id, invoice_number, total, due_amount, base_due_amount, paid_status, status, currency_id FROM invoices WHERE paid_status = 'PARTIALLY_PAID' ORDER BY due_amount ASC LIMIT 20");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("ID:%d  #%s  total:%s  due:%s  base_due:%s  paid_status:%s  status:%s  currency:%s\n",
        $row['id'], $row['invoice_number'], $row['total'], $row['due_amount'], $row['base_due_amount'], $row['paid_status'], $row['status'], $row['currency_id']);
}

echo "\n=== Payments for those invoices ===\n";
$stmt = $db->query("SELECT id, invoice_number, total, due_amount, paid_status FROM invoices WHERE invoice_number IN ('2026000031','2026000030') OR paid_status = 'PARTIALLY_PAID'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("\nInvoice #%s (ID:%d): total=%s due=%s status=%s\n", $row['invoice_number'], $row['id'], $row['total'], $row['due_amount'], $row['paid_status']);
    $pstmt = $db->prepare("SELECT id, amount, payment_date, payment_number, payment_method_id FROM payments WHERE invoice_id = ?");
    $pstmt->execute([$row['id']]);
    $totalPaid = 0;
    while ($p = $pstmt->fetch(PDO::FETCH_ASSOC)) {
        $totalPaid += $p['amount'];
        echo sprintf("  Payment ID:%d #%s: amount=%s date=%s method=%s\n", $p['id'], $p['payment_number'], $p['amount'], $p['payment_date'], $p['payment_method_id']);
    }
    echo sprintf("  Total paid: %s  Expected due: %s  Actual due: %s  Diff: %s\n", $totalPaid, $row['total'] - $totalPaid, $row['due_amount'], ($row['total'] - $totalPaid) - $row['due_amount']);
}

echo "\n=== Check if invoice 2026000031 exists at all ===\n";
$stmt = $db->query("SELECT id, invoice_number, total, due_amount, paid_status, status FROM invoices WHERE invoice_number LIKE '%000031%'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("ID:%d  #%s  total:%s  due:%s  paid_status:%s  status:%s\n", $row['id'], $row['invoice_number'], $row['total'], $row['due_amount'], $row['paid_status'], $row['status']);
}

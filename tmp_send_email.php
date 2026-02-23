<?php
/**
 * Temporary script to send email via Postmark API.
 * Run on Railway: php tmp_send_email.php
 * Delete after use.
 */

// Get Postmark token from environment
$token = getenv('POSTMARK_TOKEN') ?: ($_ENV['POSTMARK_TOKEN'] ?? '');
if (empty($token)) {
    echo "ERROR: POSTMARK_TOKEN not found in environment\n";
    exit(1);
}

$to = $argv[1] ?? 'atillatkulu@gmail.com';
$from = 'Atilla Tanrikulu <atilla@facturino.mk>';
$subject = 'Грешка при регистрација на тест околина за е-Фактура + Барање за онбординг како софтверски провајдер';

$html = '<p>Почитувани,</p>
<p>Ви се обраќам во врска со две прашања поврзани со новиот систем за е-Фактура.</p>

<p><strong>1. Грешка при регистрација на тест околина</strong></p>
<p>При обид за регистрација на тест околината на адреса https://eujptest.ujp.gov.mk/ureg, внесувањето на нашиот ЕДБ број <strong>4004026525934</strong> резултира со грешка и не е можно да се заврши регистрацијата. Ве молиме за помош околу овој проблем или информација дали е потребна претходна активација на нашиот ЕДБ за пристап до тест околината.</p>

<p><strong>2. Барање за онбординг како софтверски провајдер</strong></p>
<p>Нашата компанија развива сметководствен софтвер наменет за македонскиот пазар и сме заинтересирани за интеграција со системот е-Фактура. Би сакале да се регистрираме како софтверски провајдер и да добиеме пристап до API документацијата и тест околината.</p>

<p>Податоци за компанијата:</p>
<ul>
<li><strong>Назив:</strong> Друштво за софтвер и ИТ услуги ФАКТУРИНО ДООЕЛ Велес</li>
<li><strong>ЕМБС:</strong> 7922400</li>
<li><strong>ЕДБ:</strong> 4004026525934</li>
<li><strong>Софтвер:</strong> Facturino — сметководствена платформа (https://facturino.mk)</li>
<li><strong>Контакт лице:</strong> Atilla Tanrikulu</li>
<li><strong>Телефон:</strong> +389 70 253 467</li>
<li><strong>Е-пошта:</strong> atilla@facturino.mk</li>
</ul>

<p>Ве молиме да ни укажете на потребните чекори за онбординг и интеграција, како и за потребна документација доколку е применливо.</p>

<p>Однапред Ви благодарам за соработката.</p>

<p>Со почит,<br>
Atilla Tanrikulu<br>
Facturino ДООЕЛ Велес<br>
+389 70 253 467</p>';

$text = "Почитувани,

Ви се обраќам во врска со две прашања поврзани со новиот систем за е-Фактура.

1. Грешка при регистрација на тест околина

При обид за регистрација на тест околината на адреса https://eujptest.ujp.gov.mk/ureg, внесувањето на нашиот ЕДБ број 4004026525934 резултира со грешка и не е можно да се заврши регистрацијата. Ве молиме за помош околу овој проблем или информација дали е потребна претходна активација на нашиот ЕДБ за пристап до тест околината.

2. Барање за онбординг како софтверски провајдер

Нашата компанија развива сметководствен софтвер наменет за македонскиот пазар и сме заинтересирани за интеграција со системот е-Фактура. Би сакале да се регистрираме како софтверски провајдер и да добиеме пристап до API документацијата и тест околината.

Податоци за компанијата:
- Назив: Друштво за софтвер и ИТ услуги ФАКТУРИНО ДООЕЛ Велес
- ЕМБС: 7922400
- ЕДБ: 4004026525934
- Софтвер: Facturino — сметководствена платформа (https://facturino.mk)
- Контакт лице: Atilla Tanrikulu
- Телефон: +389 70 253 467
- Е-пошта: atilla@facturino.mk

Ве молиме да ни укажете на потребните чекори за онбординг и интеграција, како и за потребна документација доколку е применливо.

Однапред Ви благодарам за соработката.

Со почит,
Atilla Tanrikulu
Facturino ДООЕЛ Велес
+389 70 253 467";

$payload = [
    'From' => $from,
    'To' => $to,
    'Subject' => $subject,
    'HtmlBody' => $html,
    'TextBody' => $text,
    'MessageStream' => 'broadcast',
    'ReplyTo' => 'atilla@facturino.mk',
];

// Add CC if sending to UJP
if ($to !== 'atillatkulu@gmail.com') {
    $payload['Cc'] = 'atillatkulu@gmail.com';
}

$ch = curl_init('https://api.postmarkapp.com/email');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'Content-Type: application/json',
        'X-Postmark-Server-Token: ' . $token,
    ],
    CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response: $response\n";

if ($httpCode === 200) {
    $data = json_decode($response, true);
    echo "SUCCESS! MessageID: " . ($data['MessageID'] ?? 'N/A') . "\n";
    echo "Sent to: $to\n";
} else {
    echo "FAILED to send email\n";
}

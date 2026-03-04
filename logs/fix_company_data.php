<?php
require '/var/www/html/vendor/autoload.php';
$app = require_once '/var/www/html/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\Company;
use App\Models\Address;
use App\Models\Country;

// Macedonia country_id
$mk = Country::where('code', 'MK')->first();
if (!$mk) {
    echo "ERROR: Macedonia country not found in countries table!\n";
    exit(1);
}
echo "Macedonia country_id: {$mk->id}\n\n";

// Same data array from create_companies.php
// Format: [name, city, postal_prefix, address_raw, edb, mb, email, password]
$data = [
['ДЕМИРОВИЌ КОМПАНИ ДООЕЛ','СКОПЈЕ','10','00ул. 6 брМаке','MK4078012501181','6808484','demirovikkompani@outlook.com','12501181'],
['ПОПЕ И СИНОВИ ДООЕЛ','СКОПЈЕ','10','00ул. 1627Маке','MK4032017533839','7185227','popeisinovi17@gmail.com','17533839'],
['ШШК ВАРДАР','СКОПЈЕ','10','00Ул. Цветан Д','MK4030974163137','5556198','nazim.kurtovic@gmail.com','74163137'],
['А2-КОМПАНИ ДОО увоз-извоз','СКОПЈЕ','10','00Ул.2 бр.14 Д','MK4069020502465','7414412','a2kompanidoo@gmail.com','20502465'],
['МК ТЕРМ ДООЕЛ увоз-извоз','Скопје','10','00Ул. ГорнР.С.','MK4032022552175','7564031','mkterm22@gmail.com','22552175'],
['СПИБО ИНЖЕНЕРИНГ ДООЕЛ','СКОПЈЕ','10','0043 БИС бР.С.','MK4030004520417','5902975','spibo.ing@hotmail.com','04520417'],
['СПИБО КОНСТРАКШН ДООЕЛ','АЕРОДРОМ','10','00Ул.ВАСКОР.С.','MK4032012518106','6796702','spibo.construction@hotmail.com','12518106'],
['КА-АН СЕРВИС ДООЕЛ','СКОПЈЕ','10','00Ул.20 брР.С.','MK4076022502116','7606990','kaanservisdooel@gmail.com','22502116'],
['УРБАН МЕТАЛ ДООЕЛ','СКОПЈЕ','10','00ФЕРИД ЗАР.С.','MK4030008019097','6322450','urban.metal@yahoo.com','08019097'],
['БС МЕТАЛИК ДООЕЛ','ЧАИР','10','00МАКЕД.КОР.С.','MK4082020526247','7423446','bs.metalik@gmail.com','20526247'],
['ФРЕШ СФ-2020 ДООЕЛ','СКОПЈЕ','10','00МЕТОДИЈАР.С.','MK4043020528880','7438850','fatidiamanti@hotmail.com','20528880'],
['АДВОКАТ ОЛИВЕР СИМОНОВСКИ','СКОПЈЕ','10','00Ул.ФинскРС.М','MK5043021503117','7525931','simonovski.law@gmail.com','21503117'],
['АТА ТУРУНЏЕ ДООЕЛ','СКОПЈЕ','10','00Ул.ПЕРО РС.М','MK4030992212730','4380711','aturundze@yahoo.com','92212730'],
['ТЕХНОМОНТ-СД ДООЕЛ','КИСЕЛА В','10','00Ул.БорисРС.М','MK4058022541350','7576412','tehnomontsd@gmail.com','22541350'],
['БИЗНИС И СТРАТЕГИИ ЗА ИНТЕ','КАРПОШ','10','00ЊУДЕЛХИСРС.М','MK4057021555246','7506902','aneta.spirkoska@bseurope.com','21555246'],
['УРБАН-ГРАДБА Манолча Илије','КИСЕЛА В','10','00ТОМЕ АРСРС.М','MK4030993353249','4631480','urban_gradba@live.com','93353249'],
['РОК КАФАНА РУСТИКАНА ТП','КАРПОШ','10','00БУЛЕВАР ИЛИН','MK5057016502413','7118520','armand.veljan@gmail.com','16502413'],
['ФРЕШ ГРОУП ДООЕЛ','СКОПЈЕ','','2СПИРО ЦРНЕ б','MK4043023535357','7674236','demiri.fatmir@icloud.com','23535357'],
['Салон за убавина ФАВОРИ Фи','Скопје','10','00Ул. БукуР.С.','MK5057023505355','7682050','ozcelikfilis@gmail.com','23505355'],
['ДПТУ ХИТИНГ-АС ДООЕЛ','СКОПЈЕ','10','00Ул.Боро Р.С.','MK4043023535764','7682786','heating.as@hotmail.com','23535764'],
['ЕКО ВЕЛОСИПЕДИСТ','СКОПЈЕ','','БОРКА ТАРСМ','MK4082018522854','7320531','eko_velosipedist@yahoo.com','18522854'],
['БИЗНИС ПЛАН ДООЕЛ','СКОПЈЕ','10','00ЛАЗАР ПОМАКЕ','MK4043024539143','7800436','biznisplandooel@yahoo.com','24539143'],
['Д.О.О.Е.Л. БАЛКАНС СОФТ','СКОПЈЕ','','2Ул.2 Бр. Чуч','','','balkanssoft.mk@gmail.com','Facturino1'],
['ПАНТЕА ЦЕНТАР ДООЕЛ','СКОПЈЕ','10','00ХРИСТО ТМАКЕ','MK4032023556719','7657552','panthea.centar@gmail.com','23556719'],
['ЕЛЕКТРИОН 2 ДООЕЛ експорт','СКОПЈЕ','10','00БУЛЕВАР МАКЕ','MK4057024570416','7758855','electryone2.mk@gmail.com','24570416'],
['АЛЛ4ТЕЦХ ДООЕЛ','СКОПЈЕ','10','00Ул. ПероМАКЕ','MK4082015515920','7073283','tamara.dz@all4tech.rs','15515920'],
['АЕР ЈТД','СКОПЈЕ','10','00САСА 18 67','MK4023993107195','4578163','aer66@t.mk','93107195'],
['ПРОПРИНТ ПЛУС ДООЕЛ','СКОПЈЕ','10','00БУЛЕВАР Р.С.','MK4032009501499','6479642','ppp.skopje@gmail.com','09501499'],
['КОННТРА ДООЕЛ СКОПЈЕ','СКОПЈЕ','10','00УЛ. РАЗЛРС М','MK4057024573202','7815123','office@konntra.com','24573202'],
['ПАЛЕТ ПРИНТ ДИЗАЈН СТУДИО','СКОПЈЕ','','БОРИС ТРАЈКО','MK4058017526815','7199244','kate@paletprint.mk','17526815'],
['МАГНОХЕММ ДООЕЛ','СКОПЈЕ','10','0016-ТА МАРС М','MK4030003489303','5796580','magnohemm@yahoo.com','03489303'],
['КАТА ЛОЛ ДООЕЛ','СКОПЈЕ','','ФРАЊО КЛУЗ Б','MK4032024564421','7803265','katalol.mk@gmail.com','24564421'],
['ЕЛИТ-М ТРАНСПОРТ ДООЕЛ','СКОПЈЕ','','Ул. Никола Т','MK4030006591150','6104045','elitmk@hotmail.com','06591150'],
['3.С НИКОЛА ТЕСЛА БР.14','СКОПЈЕ','','','','','zsnikolatesla14@yahoo.com','Facturino1'],
['СЕНД ВИСИОН ДООЕЛ','СКОПЈЕ-К','10','00НИКОЛА РР.С.','MK4057025575373','7851464','vision.topki.mk@gmail.com','25575373'],
['ЛЕОН ЕКСПРЕС ДОО','СКОПЈЕ','10','003-ТА МАКР.С','MK4032025567238','7856725','leonekspres@gmail.com','25567238'],
['СПИБО ГРАДБА ДООЕЛ','СКОПЈЕ','10','00УЛ. ВАСКО КА','MK4058025551829','7851405','spibo.gradba@hotmail.com','25551829'],
['МАЛИОТ ЗАМОК ДООЕЛ','СКОПЈЕ','10','00УЛ.1 БР.10 Д','MK4032011513852','6708803','pavlovskitoni@gmail.com','11513852'],
['ДПТУ МОККАА ДООЕЛ','СКОПЈЕ','10','00УЛ. СВ.ПРОХО','MK4082020525704','7408927','mokkaskopje@gmail.com','20525704'],
['МИА КЛОТХИНГ ДООЕЛ','КУМАНОВО','','3-ТА МАКЕДОН','MK4017025555869','7866593','mesutersoy2018@gmail.com','25555869'],
['Д.А.В КОРПОРЕЈШН ДООЕЛ Ско','Скопје','10','00ул. Ристо Ка','MK4043024537310','7740735','davcorporation2024@gmail.com','24537310'],
['НГ 747 ДООЕЛ','ИЛИНДЕН','','Ул. 19 бр.10','MK4054024507715','7790007','ng747.mk@gmail.com','24507715'],
['АВТО МИЛДРА ДОО','НЕГОТИНО','','Ул. Кури Пес','','','milan_traevski@yahoo.com','Facturino1'],
['МИЛ ТЕА КЕТЕРИНГ ДООЕЛ','СКОПЈЕ','','Ул. Китка бр','MK4058025553341','7904223','milteaketering@gmail.com','25553341'],
['ВСС ПРОМЕТ ДООЕЛ увоз-изво','СКОПЈЕ','10','003-ТА МАКЕДОН','MK4030991105299','4265823','vsspromet@gmail.com','91105299'],
['ТУ СИСТЕРС БЕЈКАРИ ДООЕЛ С','СКОПЈЕ','10','00ПАЛМИРО ТОЛЈ','MK4043025542580','7899270','ttwosistersbakery@gmail.com','25542580'],
['НУА КОЗМЕТИК ДООЕЛ','СКОПЈЕ','10','00Ул. Владимир','MK4080025636420','7907737','info@nuacosmetic.com','25636420'],
['СОФИОВ ДИЗАЈН ДООЕЛ','СКОПЈЕ','','Ул. Св КМаке','MK4032021549260','7500661','sofiovdesign@gmail.com','21549260'],
['КАБЕЛКОП ИНВЕСТА ДООЕЛ с.','СТУДЕНИЧ','10','00Ул 2 бр. 73','MK4078015502162','7070837','kabelkopinvesta@gmail.com','15502162'],
['ДАБЕСТ-1985 ДООЕЛ с. Батин','СТУДЕНИЧ','10','00Ул. 1 бр. 25','MK4078015501972','7025696','enkokonobar@gmail.com','15501972'],
['ТОТАЛ МАРКЕТИНГ ГРУП ДООЕЛ','СКОПЈЕ','10','00Ул. ХераРСМ','MK4044025524472','7846932','total_express20@yahoo.com','25524472'],
['ИМПЕРАЛИС ГМБ ДОО увоз-изв','СКОПЈЕ','10','00БУЛЕВАР ЈАНЕ','MK4032013521305','6868720','imperalisgmbhmk@hotmail.com','13521305']
];

$updated = 0;
$skipped = 0;
$errors = [];

foreach ($data as $i => $row) {
    [$name, $city, $postalPrefix, $addressRaw, $edb, $mb, $email, $password] = $row;

    try {
        // Find user by email
        $user = User::where('email', $email)->first();
        if (!$user) {
            $errors[] = "SKIP: User not found - $email ($name)";
            $skipped++;
            continue;
        }

        // Find company owned by this user
        $company = Company::where('owner_id', $user->id)->first();
        if (!$company) {
            $errors[] = "SKIP: Company not found for user $email ($name)";
            $skipped++;
            continue;
        }

        // --- 1. Parse and clean address data ---

        // Reconstruct zip code: if postalPrefix is '10', full zip is '1000' (Skopje standard)
        $zip = '';
        $streetAddress = $addressRaw;
        if ($postalPrefix === '10' && str_starts_with($addressRaw, '00')) {
            $zip = '1000';
            $streetAddress = substr($addressRaw, 2); // Remove leading '00'
        } elseif ($postalPrefix !== '') {
            $zip = $postalPrefix . '00';
            if (str_starts_with($addressRaw, '00')) {
                $streetAddress = substr($addressRaw, 2);
            }
        }

        // Clean up truncated suffixes from address (artifacts from CSV export)
        $streetAddress = preg_replace('/\s*(Маке|МАКЕ|Р\.С\.|РС\.М|Р\.С\.М|СРС\.М|РСМ|РС М|СМ|МАКЕ|ИЛИН|67)\s*$/', '', $streetAddress);
        $streetAddress = trim($streetAddress);

        // Clean city name
        $cityClean = trim($city);

        // --- 2. Update Company vat_id, tax_id, vat_number from EDB ---
        // Strip MK prefix - Macedonian EDB numbers don't have it
        $edbClean = preg_replace('/^MK/', '', $edb);

        $companyUpdates = [];
        if ($edbClean) {
            // Always overwrite to fix the MK prefix that was saved earlier
            $companyUpdates['vat_id'] = $edbClean;
            $companyUpdates['tax_id'] = $edbClean;
            $companyUpdates['vat_number'] = $edbClean;
        }
        if ($mb) {
            $companyUpdates['registration_number'] = $mb;
        }
        if (!empty($companyUpdates)) {
            $company->update($companyUpdates);
        }

        // --- 3. Create or update Address record ---
        $existingAddress = Address::where('company_id', $company->id)->first();
        if ($existingAddress) {
            // Update existing address with missing fields
            $addressUpdates = [];
            if (!$existingAddress->city && $cityClean) $addressUpdates['city'] = $cityClean;
            if (!$existingAddress->zip && $zip) $addressUpdates['zip'] = $zip;
            if (!$existingAddress->address_street_1 && $streetAddress) $addressUpdates['address_street_1'] = $streetAddress;
            if (!$existingAddress->country_id) $addressUpdates['country_id'] = $mk->id;
            if (!empty($addressUpdates)) {
                $existingAddress->update($addressUpdates);
            }
            echo "UPD: $name | city=$cityClean | zip=$zip | addr=$streetAddress\n";
        } else {
            // Create new address
            Address::create([
                'company_id' => $company->id,
                'name' => $name,
                'address_street_1' => $streetAddress ?: null,
                'city' => $cityClean ?: null,
                'zip' => $zip ?: null,
                'country_id' => $mk->id,
                'type' => 'billing',
            ]);
            echo "NEW: $name | city=$cityClean | zip=$zip | addr=$streetAddress\n";
        }

        $updated++;
    } catch (\Exception $e) {
        $errors[] = "ERR: $name | $email | " . $e->getMessage();
    }
}

echo "\n=== SUMMARY ===\n";
echo "Updated: $updated | Skipped: $skipped | Errors: " . count($errors) . "\n";
if (!empty($errors)) {
    echo "\n=== ERRORS ===\n";
    foreach ($errors as $e) echo $e . "\n";
}

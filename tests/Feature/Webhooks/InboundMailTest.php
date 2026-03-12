<?php

use App\Jobs\ProcessInboundBillEmail;
use App\Models\Company;
use App\Models\CompanyInboundAlias;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\postJson;

beforeEach(function () {
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);

    // No auth token required in test env (config default is null)
    config(['services.postmark_inbound.token' => null]);
});

function makePostmarkPayload(array $overrides = []): array
{
    $pdfContent = base64_encode('%PDF-1.4 fake pdf content for testing');

    return array_merge([
        'From' => 'supplier@example.com',
        'FromFull' => [
            'Email' => 'supplier@example.com',
            'Name' => 'Test Supplier',
        ],
        'To' => 'bills-test@in.facturino.mk',
        'ToFull' => [
            ['Email' => 'bills-test@in.facturino.mk', 'Name' => ''],
        ],
        'Subject' => 'Invoice #123',
        'TextBody' => 'Please find attached invoice.',
        'Attachments' => [
            [
                'Name' => 'invoice.pdf',
                'Content' => $pdfContent,
                'ContentType' => 'application/pdf',
                'ContentLength' => strlen($pdfContent),
            ],
        ],
    ], $overrides);
}

test('postmark inbound with valid alias and pdf dispatches job', function () {
    $company = Company::firstOrFail();

    $alias = CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-test',
    ]);

    Bus::fake();
    Storage::fake();

    $response = postJson('/webhooks/email-inbound', makePostmarkPayload());

    $response->assertOk();

    Bus::assertDispatched(ProcessInboundBillEmail::class, function ($job) use ($company) {
        return $job->companyId === $company->id
            && $job->from === 'supplier@example.com'
            && $job->subject === 'Invoice #123'
            && count($job->attachments) === 1
            && isset($job->attachments[0]['path'])
            && isset($job->attachments[0]['original_name'])
            && $job->attachments[0]['original_name'] === 'invoice.pdf';
    });
});

test('postmark inbound with unknown alias returns 200 but no job', function () {
    Bus::fake();

    $response = postJson('/webhooks/email-inbound', makePostmarkPayload([
        'To' => 'unknown-alias@in.facturino.mk',
        'ToFull' => [['Email' => 'unknown-alias@in.facturino.mk', 'Name' => '']],
    ]));

    $response->assertOk();
    Bus::assertNotDispatched(ProcessInboundBillEmail::class);
});

test('postmark inbound accepts image attachments (png, jpeg, webp)', function () {
    $company = Company::firstOrFail();

    CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-test',
    ]);

    Bus::fake();
    Storage::fake();

    $response = postJson('/webhooks/email-inbound', makePostmarkPayload([
        'Attachments' => [
            [
                'Name' => 'invoice-photo.png',
                'Content' => base64_encode('fake png'),
                'ContentType' => 'image/png',
                'ContentLength' => 8,
            ],
        ],
    ]));

    $response->assertOk();
    Bus::assertDispatched(ProcessInboundBillEmail::class, function ($job) {
        return count($job->attachments) === 1
            && $job->attachments[0]['original_name'] === 'invoice-photo.png'
            && $job->attachments[0]['content_type'] === 'image/png';
    });
});

test('postmark inbound skips unsupported attachments (txt, doc)', function () {
    $company = Company::firstOrFail();

    CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-test',
    ]);

    Bus::fake();

    $response = postJson('/webhooks/email-inbound', makePostmarkPayload([
        'Attachments' => [
            [
                'Name' => 'notes.txt',
                'Content' => base64_encode('hello'),
                'ContentType' => 'text/plain',
                'ContentLength' => 5,
            ],
        ],
    ]));

    $response->assertOk();
    Bus::assertNotDispatched(ProcessInboundBillEmail::class);
});

test('postmark inbound processes multiple pdf attachments', function () {
    $company = Company::firstOrFail();

    CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-test',
    ]);

    Bus::fake();
    Storage::fake();

    $pdf1 = base64_encode('%PDF-1.4 invoice one');
    $pdf2 = base64_encode('%PDF-1.4 invoice two');

    $response = postJson('/webhooks/email-inbound', makePostmarkPayload([
        'Attachments' => [
            ['Name' => 'inv1.pdf', 'Content' => $pdf1, 'ContentType' => 'application/pdf', 'ContentLength' => strlen($pdf1)],
            ['Name' => 'inv2.pdf', 'Content' => $pdf2, 'ContentType' => 'application/pdf', 'ContentLength' => strlen($pdf2)],
        ],
    ]));

    $response->assertOk();

    Bus::assertDispatched(ProcessInboundBillEmail::class, function ($job) {
        return count($job->attachments) === 2;
    });
});

test('postmark inbound filters mixed attachments (keeps pdf and images, skips txt)', function () {
    $company = Company::firstOrFail();

    CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-test',
    ]);

    Bus::fake();
    Storage::fake();

    $pdf = base64_encode('%PDF-1.4 real invoice');

    $response = postJson('/webhooks/email-inbound', makePostmarkPayload([
        'Attachments' => [
            ['Name' => 'photo.jpeg', 'Content' => base64_encode('jpeg'), 'ContentType' => 'image/jpeg', 'ContentLength' => 4],
            ['Name' => 'invoice.pdf', 'Content' => $pdf, 'ContentType' => 'application/pdf', 'ContentLength' => strlen($pdf)],
            ['Name' => 'notes.txt', 'Content' => base64_encode('hello'), 'ContentType' => 'text/plain', 'ContentLength' => 5],
        ],
    ]));

    $response->assertOk();

    Bus::assertDispatched(ProcessInboundBillEmail::class, function ($job) {
        return count($job->attachments) === 2
            && $job->attachments[0]['original_name'] === 'photo.jpeg'
            && $job->attachments[1]['original_name'] === 'invoice.pdf';
    });
});

test('postmark inbound rejects request without valid auth token', function () {
    config(['services.postmark_inbound.token' => 'secret-token-123']);

    $response = postJson('/webhooks/email-inbound', makePostmarkPayload());

    $response->assertStatus(401);
});

test('postmark inbound accepts request with valid auth token via query param', function () {
    $company = Company::firstOrFail();

    CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-test',
    ]);

    config(['services.postmark_inbound.token' => 'secret-token-123']);

    Bus::fake();
    Storage::fake();

    $response = postJson('/webhooks/email-inbound?token=secret-token-123', makePostmarkPayload());

    $response->assertOk();
    Bus::assertDispatched(ProcessInboundBillEmail::class);
});

test('postmark inbound accepts request with valid auth token via Basic header', function () {
    $company = Company::firstOrFail();

    CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-test',
    ]);

    config(['services.postmark_inbound.token' => 'secret-token-123']);

    Bus::fake();
    Storage::fake();

    $response = postJson('/webhooks/email-inbound', makePostmarkPayload(), [
        'Authorization' => 'Basic secret-token-123',
    ]);

    $response->assertOk();
    Bus::assertDispatched(ProcessInboundBillEmail::class);
});

test('postmark inbound writes decoded pdf to storage', function () {
    $company = Company::firstOrFail();
    $disk = env('FILESYSTEM_DISK', 'public');
    Storage::fake($disk);

    CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-test',
    ]);

    Bus::fake();

    $pdfContent = '%PDF-1.4 test content';
    $encoded = base64_encode($pdfContent);

    postJson('/webhooks/email-inbound', makePostmarkPayload([
        'Attachments' => [
            ['Name' => 'test.pdf', 'Content' => $encoded, 'ContentType' => 'application/pdf', 'ContentLength' => strlen($encoded)],
        ],
    ]));

    Bus::assertDispatched(ProcessInboundBillEmail::class, function ($job) use ($pdfContent, $disk) {
        $path = $job->attachments[0]['path'];
        $stored = Storage::disk($disk)->get($path);

        return $stored === $pdfContent;
    });
});

test('postmark inbound handles ToFull with multiple recipients', function () {
    $company = Company::firstOrFail();

    CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-test',
    ]);

    Bus::fake();
    Storage::fake();

    $response = postJson('/webhooks/email-inbound', makePostmarkPayload([
        'To' => 'someone@example.com, bills-test@in.facturino.mk',
        'ToFull' => [
            ['Email' => 'someone@example.com', 'Name' => 'Someone'],
            ['Email' => 'bills-test@in.facturino.mk', 'Name' => ''],
        ],
    ]));

    $response->assertOk();
    Bus::assertDispatched(ProcessInboundBillEmail::class);
});

test('postmark inbound handles empty attachments array', function () {
    $company = Company::firstOrFail();

    CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-test',
    ]);

    Bus::fake();

    $response = postJson('/webhooks/email-inbound', makePostmarkPayload([
        'Attachments' => [],
    ]));

    $response->assertOk();
    Bus::assertNotDispatched(ProcessInboundBillEmail::class);
});

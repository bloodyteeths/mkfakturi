<?php

use App\Jobs\ProcessInboundBillEmail;
use App\Models\Company;
use App\Models\CompanyInboundAlias;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;

use function Pest\Laravel\post;

beforeEach(function () {
    Artisan::call('migrate', ['--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
    Artisan::call('db:seed', ['--class' => 'DemoSeeder', '--force' => true]);
});

test('inbound mail with valid alias and pdf dispatches ProcessInboundBillEmail job', function () {
    $company = Company::firstOrFail();

    $alias = CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-'.$company->id,
    ]);

    Bus::fake();

    $file = UploadedFile::fake()->create('invoice.pdf', 10, 'application/pdf');

    $response = post('/webhooks/email-inbound', [
        'to' => $alias->alias.'@example.test',
        'from' => 'supplier@example.com',
        'subject' => 'Test Invoice',
        'attachments' => [$file],
    ]);

    $response->assertOk();

    Bus::assertDispatched(ProcessInboundBillEmail::class, function (ProcessInboundBillEmail $job) use ($company) {
        return $job->companyId === $company->id
            && $job->from === 'supplier@example.com'
            && $job->subject === 'Test Invoice'
            && count($job->attachments) === 1
            && isset($job->attachments[0]['path']);
    });
});

test('inbound mail with unknown alias does not dispatch job', function () {
    Bus::fake();

    $file = UploadedFile::fake()->create('invoice.pdf', 10, 'application/pdf');

    $response = post('/webhooks/email-inbound', [
        'to' => 'unknown-alias@example.test',
        'from' => 'supplier@example.com',
        'subject' => 'Test Invoice',
        'attachments' => [$file],
    ]);

    $response->assertOk();

    Bus::assertNotDispatched(ProcessInboundBillEmail::class);
});

test('inbound mail skips non-pdf attachments', function () {
    $company = Company::firstOrFail();

    CompanyInboundAlias::create([
        'company_id' => $company->id,
        'alias' => 'bills-'.$company->id,
    ]);

    Bus::fake();

    $file = UploadedFile::fake()->create('image.png', 10, 'image/png');

    $response = post('/webhooks/email-inbound', [
        'to' => 'bills-'.$company->id.'@example.test',
        'from' => 'supplier@example.com',
        'subject' => 'Test Invoice',
        'attachments' => [$file],
    ]);

    $response->assertOk();

    Bus::assertNotDispatched(ProcessInboundBillEmail::class);
});

<?php

use App\Models\CompanySetting;
use App\Models\Currency;
use App\Models\CustomField;
use App\Models\Setting;
use App\Space\InstallUtils;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Get company setting
 *
 * @return string
 */
function get_company_setting($key, $company_id)
{
    if (! InstallUtils::isDbCreated()) {
        return null;
    }

    return CompanySetting::getSetting($key, $company_id);
}

/**
 * Get app setting
 *
 * @param  $company_id
 * @return string
 */
function get_app_setting($key)
{
    if (! InstallUtils::isDbCreated()) {
        return null;
    }

    return Setting::getSetting($key);
}

/**
 * Get page title
 *
 * @return string
 */
function get_page_title($company_id)
{
    if (! InstallUtils::isDbCreated()) {
        return null;
    }

    $routeName = Route::currentRouteName();

    $defaultPageTitle = 'InvoiceShelf - Self Hosted Invoicing Platform';

    if ($routeName === 'customer.dashboard') {
        $pageTitle = CompanySetting::getSetting('customer_portal_page_title', $company_id);

        return $pageTitle ? $pageTitle : $defaultPageTitle;
    }

    $pageTitle = Setting::getSetting('admin_page_title');

    return $pageTitle ? $pageTitle : $defaultPageTitle;
}

/**
 * Set Active Path
 *
 * @param  string  $active
 * @return string
 */
function set_active($path, $active = 'active')
{
    return call_user_func_array('Request::is', (array) $path) ? $active : '';
}

/**
 * @return mixed
 */
function is_url($path)
{
    return call_user_func_array('Request::is', (array) $path);
}

/**
 * @return string
 */
function getCustomFieldValueKey(string $type)
{
    switch ($type) {
        case 'Input':
            return 'string_answer';

        case 'TextArea':
            return 'string_answer';

        case 'Phone':
            return 'number_answer';

        case 'Url':
            return 'string_answer';

        case 'Number':
            return 'number_answer';

        case 'Dropdown':
            return 'string_answer';

        case 'Switch':
            return 'boolean_answer';

        case 'Date':
            return 'date_answer';

        case 'Time':
            return 'time_answer';

        case 'DateTime':
            return 'date_time_answer';

        default:
            return 'string_answer';
    }
}

/**
 * Resolve a logo reference ensuring it exists locally or is a valid remote URL.
 */
function resolve_logo_reference(?string $path): ?string
{
    if (! $path) {
        return null;
    }

    if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
        return $path;
    }

    $disks = array_unique([config('filesystems.default', 'public'), 'public']);

    foreach ($disks as $disk) {
        if (! $disk || ! config("filesystems.disks.{$disk}")) {
            continue;
        }

        try {
            if (Storage::disk($disk)->exists($path)) {
                return $path;
            }
        } catch (Throwable $exception) {
            continue;
        }
    }

    return null;
}

/**
 * Return an absolute URL for a logo reference or null if it cannot be resolved.
 */
function logo_asset_url(?string $path): ?string
{
    $path = resolve_logo_reference($path);

    if (! $path) {
        return null;
    }

    if (Str::startsWith($path, ['http://', 'https://', '//', 'data:'])) {
        return $path;
    }

    $disks = array_unique([config('filesystems.default', 'public'), 'public']);

    foreach ($disks as $disk) {
        if (! $disk || ! config("filesystems.disks.{$disk}")) {
            continue;
        }

        try {
            $url = Storage::disk($disk)->url($path);
            if ($url) {
                return $url;
            }
        } catch (Throwable $exception) {
            continue;
        }
    }

    return asset('storage/'.$path);
}

/**
 * @return formated_money
 */
function format_money_pdf($money, $currency = null)
{
    if (! $currency) {
        $currency = Currency::findOrFail(CompanySetting::getSetting('currency', 1));
    }

    // CRITICAL FIX: Only divide by 100 for currencies with decimal places (precision > 0)
    // For zero-precision currencies like MKD, amount is already in the correct unit
    // Example: MKD with amount=12000 stays as 12000 (12 thousand denars)
    // USD with amount=12000 becomes 120.00 (120 dollars, stored as cents)
    if ($currency->precision > 0) {
        $money = $money / 100;
    }

    $format_money = number_format(
        $money,
        $currency->precision,
        $currency->decimal_separator,
        $currency->thousand_separator
    );

    $currency_with_symbol = '';
    if ($currency->swap_currency_symbol) {
        $currency_with_symbol = $format_money.'<span style="font-family: DejaVu Sans;">'.$currency->symbol.'</span>';
    } else {
        $currency_with_symbol = '<span style="font-family: DejaVu Sans;">'.$currency->symbol.'</span>'.$format_money;
    }

    return $currency_with_symbol;
}

/**
 * @param  $string
 * @return string
 */
function clean_slug($model, $title, $id = 0)
{
    // Normalize the title
    $slug = Str::upper('CUSTOM_'.$model.'_'.Str::slug($title, '_'));

    // Get any that could possibly be related.
    // This cuts the queries down by doing it once.
    $allSlugs = getRelatedSlugs($model, $slug, $id);

    // If we haven't used it before then we are all good.
    if (! $allSlugs->contains('slug', $slug)) {
        return $slug;
    }

    // Just append numbers like a savage until we find not used.
    for ($i = 1; $i <= 10; $i++) {
        $newSlug = $slug.'_'.$i;
        if (! $allSlugs->contains('slug', $newSlug)) {
            return $newSlug;
        }
    }

    throw new \Exception('Can not create a unique slug');
}

function getRelatedSlugs($type, $slug, $id = 0)
{
    return CustomField::select('slug')->where('slug', 'like', $slug.'%')
        ->where('model_type', $type)
        ->where('id', '<>', $id)
        ->get();
}

function respondJson($error, $message)
{
    return response()->json([
        'error' => $error,
        'message' => $message,
    ], 422);
}

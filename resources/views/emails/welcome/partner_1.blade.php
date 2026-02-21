@component('mail::message')
<p>@lang('welcome.partner_1.greeting', ['name' => $name])</p>

<p>@lang('welcome.partner_1.hook')</p>

<p>@lang('welcome.partner_1.body')</p>

<p>@lang('welcome.partner_1.steps', ['appUrl' => $appUrl])</p>

<p>@lang('welcome.partner_1.cta_text') {{ $appUrl }}</p>

<p>@lang('welcome.partner_1.closing')</p>

<p>
@lang('welcome.signature_closing')<br>
<strong>@lang('welcome.signature_name')</strong><br>
@lang('welcome.signature_company')<br>
@lang('welcome.signature_phone')<br>
<a href="https://facturino.mk">facturino.mk</a>
</p>
@endcomponent

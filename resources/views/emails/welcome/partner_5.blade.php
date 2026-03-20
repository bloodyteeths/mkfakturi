@component('mail::message')
<p>@lang('welcome.partner_5.greeting', ['name' => $name])</p>

<p>@lang('welcome.partner_5.hook')</p>

<p>@lang('welcome.partner_5.body')</p>

<p>@lang('welcome.partner_5.training')</p>

<p>@lang('welcome.partner_5.cta_text') {{ $ctaUrl }}</p>

<p>@lang('welcome.partner_5.closing')</p>

<p>
@lang('welcome.signature_closing')<br>
<strong>@lang('welcome.signature_name')</strong><br>
@lang('welcome.signature_company')<br>
@lang('welcome.signature_phone')<br>
<a href="https://facturino.mk">facturino.mk</a>
</p>
{{-- CLAUDE-CHECKPOINT --}}
@endcomponent

@component('mail::message')
<p>@lang('outreach.initial.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.initial.intro')</p>

<p>@lang('outreach.initial.pitch')</p>

<p>@lang('outreach.initial.pilot')</p>

<p><strong>@lang('outreach.initial.client_benefits_title')</strong></p>

<ul>
<li>@lang('outreach.initial.client_efaktura')</li>
<li>@lang('outreach.initial.client_bank')</li>
<li>@lang('outreach.initial.client_mpin')</li>
<li>@lang('outreach.initial.client_ai')</li>
</ul>

<p><strong>@lang('outreach.initial.partner_benefits_title')</strong></p>

<ul>
<li>@lang('outreach.initial.partner_portal')</li>
<li>@lang('outreach.initial.partner_deadlines')</li>
<li>@lang('outreach.initial.partner_reports')</li>
<li>@lang('outreach.initial.partner_commission')</li>
<li>@lang('outreach.initial.partner_free')</li>
</ul>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.initial.cta')
@endcomponent

<p><small style="color: #999;">@lang('outreach.initial.opt_out')</small></p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

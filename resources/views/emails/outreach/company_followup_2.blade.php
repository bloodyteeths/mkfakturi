@component('mail::message')
<p>@lang('outreach.company_followup2.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.company_followup2.trial_offer')</p>

<p>@lang('outreach.company_followup2.features')</p>

<p><strong>@lang('outreach.company_followup2.pricing_title')</strong></p>

<ul>
<li>@lang('outreach.company_followup2.pricing_starter')</li>
<li>@lang('outreach.company_followup2.pricing_standard')</li>
<li>@lang('outreach.company_followup2.pricing_business')</li>
</ul>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup2.cta')
@endcomponent

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

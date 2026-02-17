@component('mail::message')
<p>@lang('outreach.company_initial.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.company_initial.pain_point')</p>

<p>@lang('outreach.company_initial.solution')</p>

<ul>
<li>@lang('outreach.company_initial.feature_efaktura')</li>
<li>@lang('outreach.company_initial.feature_bank')</li>
<li>@lang('outreach.company_initial.feature_recurring')</li>
<li>@lang('outreach.company_initial.feature_pdf')</li>
<li>@lang('outreach.company_initial.feature_mpin')</li>
</ul>

<p>@lang('outreach.company_initial.pricing_note')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_initial.cta')
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

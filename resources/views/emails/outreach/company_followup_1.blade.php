@component('mail::message')
<p>@lang('outreach.company_followup1.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.company_followup1.hook')</p>

<p>@lang('outreach.company_followup1.solution')</p>

<p>@lang('outreach.company_followup1.banks')</p>

<p>@lang('outreach.company_followup1.social')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup1.cta')
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

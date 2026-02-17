@component('mail::message')
<p>@lang('outreach.company_followup3.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.company_followup3.hook')</p>

<p>@lang('outreach.company_followup3.ai_intro')<br>
&bull; @lang('outreach.company_followup3.feature_chat')<br>
&bull; @lang('outreach.company_followup3.feature_forecast')<br>
&bull; @lang('outreach.company_followup3.feature_risk')<br>
&bull; @lang('outreach.company_followup3.feature_reminders')<br>
&bull; @lang('outreach.company_followup3.feature_recurring')</p>

<p>@lang('outreach.company_followup3.closing')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup3.cta')
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

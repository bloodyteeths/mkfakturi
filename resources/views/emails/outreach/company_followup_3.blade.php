@component('mail::message')
@lang('outreach.company_followup3.greeting', ['companyName' => $companyName])

@lang('outreach.company_followup3.efaktura_urgency')

@lang('outreach.company_followup3.solution')

@lang('outreach.company_followup3.social')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup3.cta')
@endcomponent

@lang('outreach.signature_closing')<br>
**@lang('outreach.signature_name')**<br>
@lang('outreach.signature_company')<br>
[{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

@component('mail::message')
@lang('outreach.company_followup1.greeting', ['companyName' => $companyName])

@lang('outreach.company_followup1.hook')

@lang('outreach.company_followup1.solution')

@lang('outreach.company_followup1.banks')

@lang('outreach.company_followup1.social')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup1.cta')
@endcomponent

@lang('outreach.signature_closing')<br>
**@lang('outreach.signature_name')**<br>
@lang('outreach.signature_company')<br>
[{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

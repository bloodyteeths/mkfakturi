@component('mail::message')
@lang('outreach.company_initial.greeting', ['companyName' => $companyName])

@lang('outreach.company_initial.pain_point')

@lang('outreach.company_initial.solution')

- @lang('outreach.company_initial.feature_efaktura')
- @lang('outreach.company_initial.feature_bank')
- @lang('outreach.company_initial.feature_recurring')
- @lang('outreach.company_initial.feature_pdf')
- @lang('outreach.company_initial.feature_mpin')

@lang('outreach.company_initial.pricing_note')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_initial.cta')
@endcomponent

@lang('outreach.signature_closing')<br>
**@lang('outreach.signature_name')**<br>
@lang('outreach.signature_company')<br>
[{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

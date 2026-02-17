@component('mail::message')
@lang('outreach.company_followup2.greeting', ['companyName' => $companyName])

@lang('outreach.company_followup2.trial_offer')

@lang('outreach.company_followup2.features')

**@lang('outreach.company_followup2.pricing_title')**

- @lang('outreach.company_followup2.pricing_starter')
- @lang('outreach.company_followup2.pricing_standard')
- @lang('outreach.company_followup2.pricing_business')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup2.cta')
@endcomponent

@lang('outreach.signature_closing')<br>
**@lang('outreach.signature_name')**<br>
@lang('outreach.signature_company')<br>
[{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

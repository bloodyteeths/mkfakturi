@component('mail::message')
@lang('outreach.company_followup4.greeting', ['companyName' => $companyName])

@lang('outreach.company_followup4.last_chance')

@lang('outreach.company_followup4.offer')

@lang('outreach.company_followup4.pricing')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup4.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.company_followup4.final_note')</small>

@lang('outreach.signature_closing')<br>
**@lang('outreach.signature_name')**<br>
@lang('outreach.signature_company')<br>
[{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

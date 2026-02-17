@component('mail::message')
@lang('outreach.followup4.greeting', ['companyName' => $companyName])

@lang('outreach.followup4.urgency')

@lang('outreach.followup4.solution')

@lang('outreach.followup4.partner_offer')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup4.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.followup4.final_note')</small>

@lang('outreach.signature_closing')<br>
**@lang('outreach.signature_name')**<br>
@lang('outreach.signature_company')<br>
[{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

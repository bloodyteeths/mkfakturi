@component('mail::message')
@lang('outreach.initial.greeting', ['companyName' => $companyName])

@lang('outreach.initial.intro')

@lang('outreach.initial.pitch')

@lang('outreach.initial.pilot')

**@lang('outreach.initial.client_benefits_title')**

- @lang('outreach.initial.client_efaktura')
- @lang('outreach.initial.client_bank')
- @lang('outreach.initial.client_mpin')
- @lang('outreach.initial.client_ai')

**@lang('outreach.initial.partner_benefits_title')**

- @lang('outreach.initial.partner_portal')
- @lang('outreach.initial.partner_deadlines')
- @lang('outreach.initial.partner_reports')
- @lang('outreach.initial.partner_commission')
- @lang('outreach.initial.partner_free')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.initial.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.initial.opt_out')</small>

@lang('outreach.signature_closing')<br>
**@lang('outreach.signature_name')**<br>
@lang('outreach.signature_company')<br>
[{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

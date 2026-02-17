@component('mail::message')
@lang('outreach.company_followup1.greeting')

@lang('outreach.company_followup1.bank_intro')

@lang('outreach.company_followup1.banks_list')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup1.cta')
@endcomponent

@lang('outreach.signature_closing')
**@lang('outreach.signature_name')**
@lang('outreach.signature_company') | [{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

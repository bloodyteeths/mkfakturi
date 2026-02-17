@component('mail::message')
@lang('outreach.followup1.greeting')

@lang('outreach.followup1.reference')

@lang('outreach.followup1.metric')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup1.cta')
@endcomponent

@lang('outreach.signature_closing')
**@lang('outreach.signature_name')**
@lang('outreach.signature_company') | [{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

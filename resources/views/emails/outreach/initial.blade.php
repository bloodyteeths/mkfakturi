@component('mail::message')
@lang('outreach.initial.greeting')

@lang('outreach.initial.intro')

@lang('outreach.initial.benefit')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.initial.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.initial.opt_out')</small>

@lang('outreach.signature_closing')
**@lang('outreach.signature_name')**
@lang('outreach.signature_company') | [{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

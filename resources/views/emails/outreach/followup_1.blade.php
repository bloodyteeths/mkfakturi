@component('mail::message')
@lang('outreach.followup1.greeting', ['companyName' => $companyName])

@lang('outreach.followup1.reference')

@lang('outreach.followup1.hook')

- @lang('outreach.followup1.feature_psd2')
- @lang('outreach.followup1.feature_auto')
- @lang('outreach.followup1.feature_csv')

@lang('outreach.followup1.partner_value')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup1.cta')
@endcomponent

@lang('outreach.signature_closing')<br>
**@lang('outreach.signature_name')**<br>
@lang('outreach.signature_company')<br>
[{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

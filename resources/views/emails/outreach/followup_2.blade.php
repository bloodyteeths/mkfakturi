@component('mail::message')
@lang('outreach.followup2.greeting', ['companyName' => $companyName])

@lang('outreach.followup2.busy')

@lang('outreach.followup2.offer')

**@lang('outreach.followup2.what_you_get_title')**

- @lang('outreach.followup2.portal_feature')
- @lang('outreach.followup2.deadlines_feature')
- @lang('outreach.followup2.reports_feature')
- @lang('outreach.followup2.commission_feature')

@lang('outreach.followup2.why_free')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup2.cta')
@endcomponent

@lang('outreach.signature_closing')<br>
**@lang('outreach.signature_name')**<br>
@lang('outreach.signature_company')<br>
[{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

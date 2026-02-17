@component('mail::message')
@lang('outreach.followup3.greeting', ['companyName' => $companyName])

@lang('outreach.followup3.hook')

- @lang('outreach.followup3.reason_portal')
- @lang('outreach.followup3.reason_deadlines')
- @lang('outreach.followup3.reason_reports')
- @lang('outreach.followup3.reason_efaktura')
- @lang('outreach.followup3.reason_free')

@lang('outreach.followup3.pricing')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup3.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.followup3.no_card')</small>

@lang('outreach.signature_closing')<br>
**@lang('outreach.signature_name')**<br>
@lang('outreach.signature_company')<br>
[{{ __('outreach.signature_url') }}](https://{{ __('outreach.signature_url') }}) | @lang('outreach.signature_phone')

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

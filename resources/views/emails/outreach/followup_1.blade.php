@component('mail::message')
<p>@lang('outreach.followup1.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.followup1.direct_pitch')</p>

<p>@lang('outreach.followup1.offer_title')<br>
&bull; @lang('outreach.followup1.offer_commission')<br>
&bull; @lang('outreach.followup1.offer_recurring')<br>
&bull; @lang('outreach.followup1.offer_free')<br>
&bull; @lang('outreach.followup1.offer_feedback')</p>

<p><strong>@lang('outreach.followup1.april_title')</strong><br>
@lang('outreach.followup1.april_desc')</p>

<p>@lang('outreach.followup1.response_ask')<br>
1. @lang('outreach.followup1.response_1')<br>
2. @lang('outreach.followup1.response_2')</p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

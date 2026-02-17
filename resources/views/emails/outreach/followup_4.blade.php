@component('mail::message')
<p>@lang('outreach.followup4.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.followup4.last_message')</p>

<p>@lang('outreach.followup4.urgency')</p>

<p>@lang('outreach.followup4.conference')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup4.cta')
@endcomponent

<p><small style="color: #999;">@lang('outreach.followup4.farewell')</small></p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

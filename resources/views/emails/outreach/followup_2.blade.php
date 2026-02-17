@component('mail::message')
<p>@lang('outreach.followup2.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.followup2.busy')</p>

<p>@lang('outreach.followup2.building_together')<br>
&bull; @lang('outreach.followup2.feedback_dashboard')<br>
&bull; @lang('outreach.followup2.feedback_deadlines')<br>
&bull; @lang('outreach.followup2.feedback_reports')</p>

<p>@lang('outreach.followup2.revenue')</p>

<p>@lang('outreach.followup2.pilot_reminder')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup2.cta')
@endcomponent

<p>@lang('outreach.followup2.conference')</p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

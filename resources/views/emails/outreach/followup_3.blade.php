@component('mail::message')
<p>@lang('outreach.followup3.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.followup3.hook')</p>

<ul>
<li>@lang('outreach.followup3.reason_portal')</li>
<li>@lang('outreach.followup3.reason_deadlines')</li>
<li>@lang('outreach.followup3.reason_reports')</li>
<li>@lang('outreach.followup3.reason_efaktura')</li>
<li>@lang('outreach.followup3.reason_free')</li>
</ul>

<p>@lang('outreach.followup3.pricing')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup3.cta')
@endcomponent

<p><small style="color: #999;">@lang('outreach.followup3.no_card')</small></p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

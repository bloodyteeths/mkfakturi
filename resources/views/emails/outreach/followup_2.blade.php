@component('mail::message')
<p>@lang('outreach.followup2.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.followup2.busy')</p>

<p>@lang('outreach.followup2.offer')</p>

<p><strong>@lang('outreach.followup2.what_you_get_title')</strong></p>

<ul>
<li>@lang('outreach.followup2.portal_feature')</li>
<li>@lang('outreach.followup2.deadlines_feature')</li>
<li>@lang('outreach.followup2.reports_feature')</li>
<li>@lang('outreach.followup2.commission_feature')</li>
</ul>

<p>@lang('outreach.followup2.why_free')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup2.cta')
@endcomponent

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

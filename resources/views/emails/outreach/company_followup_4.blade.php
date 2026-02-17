@component('mail::message')
<p>@lang('outreach.company_followup4.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.company_followup4.hook')</p>

<p>@lang('outreach.company_followup4.inspection')</p>

<p>@lang('outreach.company_followup4.legacy')</p>

<p>@lang('outreach.company_followup4.solution')<br>
&bull; @lang('outreach.company_followup4.feature_efaktura')<br>
&bull; @lang('outreach.company_followup4.feature_fiscal')<br>
&bull; @lang('outreach.company_followup4.feature_cloud')<br>
&bull; @lang('outreach.company_followup4.feature_reports')<br>
&bull; @lang('outreach.company_followup4.feature_projects')</p>

<p>@lang('outreach.company_followup4.pricing')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup4.cta')
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

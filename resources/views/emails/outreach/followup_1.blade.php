@component('mail::message')
<p>@lang('outreach.followup1.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.followup1.reference')</p>

<p>@lang('outreach.followup1.hook')</p>

<ul>
<li>@lang('outreach.followup1.feature_psd2')</li>
<li>@lang('outreach.followup1.feature_auto')</li>
<li>@lang('outreach.followup1.feature_csv')</li>
</ul>

<p>@lang('outreach.followup1.partner_value')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup1.cta')
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

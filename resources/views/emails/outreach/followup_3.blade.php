@component('mail::message')
<p>@lang('outreach.followup3.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.followup3.social_proof')<br>
&bull; @lang('outreach.followup3.impression_dashboard')<br>
&bull; @lang('outreach.followup3.impression_deadlines')<br>
&bull; @lang('outreach.followup3.impression_bank')<br>
&bull; @lang('outreach.followup3.impression_efaktura')<br>
&bull; @lang('outreach.followup3.impression_revenue')</p>

<p>@lang('outreach.followup3.still_invited')</p>

<p>@lang('outreach.followup3.conference')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup3.cta')
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

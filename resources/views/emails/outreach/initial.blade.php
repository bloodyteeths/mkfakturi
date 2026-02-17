@component('mail::message')
<p>@lang('outreach.initial.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.initial.intro')</p>

<p>@lang('outreach.initial.how_we_found')</p>

<p>@lang('outreach.initial.why_you')</p>

<p><strong>@lang('outreach.initial.partnership_title')</strong><br>
&bull; @lang('outreach.initial.partner_portal')<br>
&bull; @lang('outreach.initial.client_features')<br>
&bull; @lang('outreach.initial.commission')<br>
&bull; @lang('outreach.initial.dashboard')</p>

<p>@lang('outreach.initial.conference')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.initial.cta')
@endcomponent

<p>@lang('outreach.initial.awaiting')</p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

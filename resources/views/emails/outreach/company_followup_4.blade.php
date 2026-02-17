@component('mail::message')
<p>@lang('outreach.company_followup4.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.company_followup4.last_chance')</p>

<p>@lang('outreach.company_followup4.offer')</p>

<p>@lang('outreach.company_followup4.pricing')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup4.cta')
@endcomponent

<p><small style="color: #999;">@lang('outreach.company_followup4.final_note')</small></p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

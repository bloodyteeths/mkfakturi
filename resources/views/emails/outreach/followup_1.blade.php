@component('mail::message')
<p>@lang('outreach.followup1.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.followup1.reference')</p>

<p><strong>@lang('outreach.followup1.help_title')</strong><br>
&bull; @lang('outreach.followup1.test_psd2')<br>
&bull; @lang('outreach.followup1.test_reconciliation')<br>
&bull; @lang('outreach.followup1.test_mpin')<br>
&bull; @lang('outreach.followup1.test_efaktura')</p>

<p>@lang('outreach.followup1.free_note')</p>

<p>@lang('outreach.followup1.conference')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup1.cta')
@endcomponent

<p>@lang('outreach.followup1.awaiting')</p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

---
<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

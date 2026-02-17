@component('mail::message')
<p>@lang('outreach.company_initial.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.company_initial.hook')</p>

<p>@lang('outreach.company_initial.solution')<br>
&bull; @lang('outreach.company_initial.feature_inventory')<br>
&bull; @lang('outreach.company_initial.feature_payroll')<br>
&bull; @lang('outreach.company_initial.feature_fiscal')<br>
&bull; @lang('outreach.company_initial.feature_bank')<br>
&bull; @lang('outreach.company_initial.feature_ai')<br>
&bull; @lang('outreach.company_initial.feature_ecommerce')</p>

<p>@lang('outreach.company_initial.pricing')</p>

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_initial.cta')
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

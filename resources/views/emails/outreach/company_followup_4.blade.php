@extends('emails.outreach._plain_layout')

@section('content')
<p>@lang('outreach.company_followup4.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.company_followup4.final')</p>

<p>@lang('outreach.company_followup4.if_yes')</p>

<p>@lang('outreach.company_followup4.if_no')</p>

<p><a href="{{ $signupUrl }}" style="color: #1a73e8; font-weight: bold; font-size: 16px; text-decoration: underline;">@lang('outreach.company_followup4.cta') &rarr;</a></p>

<p><em>@lang('outreach.company_followup4.fomo')</em></p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

<p style="font-size: 12px; color: #999;"><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></p>
@endsection

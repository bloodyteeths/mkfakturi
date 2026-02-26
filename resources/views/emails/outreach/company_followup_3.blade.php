@extends('emails.outreach._plain_layout')

@section('content')
<p>@lang('outreach.company_followup3.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.company_followup3.hook')</p>

<p>
&mdash; „@lang('outreach.company_followup3.example_1')"<br>
&mdash; „@lang('outreach.company_followup3.example_2')"<br>
&mdash; „@lang('outreach.company_followup3.example_3')"
</p>

<p>@lang('outreach.company_followup3.unique')</p>

<p>@lang('outreach.company_followup3.cta'):<br>
<a href="{{ $signupUrl }}">{{ $signupUrl }}</a></p>

<p><em>@lang('outreach.company_followup3.fomo')</em></p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

<p style="font-size: 12px; color: #999;"><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></p>
@endsection

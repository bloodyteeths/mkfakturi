@extends('emails.outreach._plain_layout')

@section('content')
<p>@lang('outreach.followup1.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.followup1.intro')</p>

<p>
@lang('outreach.followup1.math_1')<br>
@lang('outreach.followup1.math_2')<br>
@lang('outreach.followup1.math_3')
</p>

<p>@lang('outreach.followup1.recurring')</p>

<p>@lang('outreach.followup1.easy')</p>

<p>@lang('outreach.followup1.cta'):<br>
<a href="{{ $signupUrl }}">{{ $signupUrl }}</a></p>

<p><em>@lang('outreach.followup1.fomo')</em></p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

<p style="font-size: 12px; color: #999;"><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></p>
@endsection

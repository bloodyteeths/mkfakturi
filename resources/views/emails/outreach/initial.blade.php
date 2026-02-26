@extends('emails.outreach._plain_layout')

@section('content')
<p>@lang('outreach.initial.greeting', ['companyName' => $companyName])</p>

<p>@lang('outreach.initial.body')</p>

<p>@lang('outreach.initial.pitch')</p>

<p>
&mdash; @lang('outreach.initial.benefit_1')<br>
&mdash; @lang('outreach.initial.benefit_2')<br>
&mdash; @lang('outreach.initial.benefit_3')
</p>

<p>@lang('outreach.initial.revenue')</p>

<p>@lang('outreach.initial.cta'):<br>
<a href="{{ $signupUrl }}">{{ $signupUrl }}</a></p>

<p><em>@lang('outreach.initial.fomo')</em></p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name')</strong><br>
@lang('outreach.signature_company')<br>
<a href="https://{{ __('outreach.signature_url') }}">{{ __('outreach.signature_url') }}</a> | @lang('outreach.signature_phone')
</p>

<p style="font-size: 12px; color: #999;"><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></p>
@endsection

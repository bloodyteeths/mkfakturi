@extends('emails.outreach._plain_layout')

@section('content')
<p>@lang('outreach.followup3.greeting')</p>

<p>@lang('outreach.followup3.line1')</p>

<p>@lang('outreach.followup3.line2')</p>

<p>@lang('outreach.followup3.line3')</p>

<p>@lang('outreach.followup3.line4')</p>

<p>@lang('outreach.followup3.line5')</p>

<p>
@lang('outreach.signature_closing')<br>
<strong>@lang('outreach.signature_name_outreach')</strong><br>
@lang('outreach.signature_company_outreach')<br>
@lang('outreach.signature_address')<br>
@lang('outreach.signature_phone_outreach')
</p>

<p style="font-size: 12px; color: #999;"><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></p>
@endsection

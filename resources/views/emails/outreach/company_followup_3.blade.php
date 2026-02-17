@component('mail::message')
@lang('outreach.company_followup3.greeting')

@lang('outreach.company_followup3.efaktura_urgency')

@lang('outreach.company_followup3.ready')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup3.cta')
@endcomponent

<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

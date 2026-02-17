@component('mail::message')
@lang('outreach.followup1.greeting')

@lang('outreach.followup1.reference')

@lang('outreach.followup1.metric')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup1.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.initial.opt_out')</small>

<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

@component('mail::message')
@lang('outreach.followup2.greeting')

@lang('outreach.followup2.busy')

@lang('outreach.followup2.offer')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup2.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.followup2.last_email')</small>

<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

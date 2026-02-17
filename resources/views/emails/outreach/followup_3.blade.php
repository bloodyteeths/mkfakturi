@component('mail::message')
@lang('outreach.followup3.greeting')

@lang('outreach.followup3.trial_offer')

@lang('outreach.followup3.features')

@lang('outreach.followup3.pricing')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup3.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.followup3.no_card')</small>

<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

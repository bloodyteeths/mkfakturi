@component('mail::message')
@lang('outreach.company_followup2.greeting')

@lang('outreach.company_followup2.trial_offer')

@lang('outreach.company_followup2.pricing')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup2.cta')
@endcomponent

<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

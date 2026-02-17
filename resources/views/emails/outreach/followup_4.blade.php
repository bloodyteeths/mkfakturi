@component('mail::message')
@lang('outreach.followup4.greeting')

@lang('outreach.followup4.urgency')

@lang('outreach.followup4.partner_offer')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.followup4.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.followup4.final_note')</small>

<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

@component('mail::message')
@lang('outreach.company_followup4.greeting')

@lang('outreach.company_followup4.last_chance')

@lang('outreach.company_followup4.comparison')

@component('mail::button', ['url' => $signupUrl])
@lang('outreach.company_followup4.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.company_followup4.final_note')</small>

<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

@component('mail::message')
@lang('outreach.partner_invite.greeting', ['name' => $partnerName])

@lang('outreach.partner_invite.congrats')

@lang('outreach.partner_invite.benefits')

@component('mail::button', ['url' => $activationUrl])
@lang('outreach.partner_invite.cta')
@endcomponent

@lang('outreach.partner_invite.contact')
@endcomponent

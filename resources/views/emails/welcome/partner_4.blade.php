@component('mail::message')
@lang('welcome.partner_4.greeting', ['name' => $name])

@lang('welcome.partner_4.hook')

@lang('welcome.partner_4.body')

@lang('welcome.partner_4.payout')

@lang('welcome.partner_4.cta_text') {{ $ctaUrl }}

@lang('welcome.partner_4.closing')

@lang('welcome.signature_closing')
**@lang('welcome.signature_name')**
@lang('welcome.signature_company')
@lang('welcome.signature_phone')
[facturino.mk](https://facturino.mk)
@endcomponent

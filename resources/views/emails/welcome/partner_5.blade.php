@component('mail::message')
@lang('welcome.partner_5.greeting', ['name' => $name])

@lang('welcome.partner_5.hook')

@lang('welcome.partner_5.body')

@lang('welcome.partner_5.training')

@lang('welcome.partner_5.cta_text') {{ $ctaUrl }}

@lang('welcome.partner_5.closing')

@lang('welcome.signature_closing')
**@lang('welcome.signature_name')**
@lang('welcome.signature_company')
@lang('welcome.signature_phone')
[facturino.mk](https://facturino.mk)
@endcomponent

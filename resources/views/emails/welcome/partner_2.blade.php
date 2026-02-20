@component('mail::message')
@lang('welcome.partner_2.greeting', ['name' => $name])

@lang('welcome.partner_2.hook')

@lang('welcome.partner_2.body')

@lang('welcome.partner_2.features')

@lang('welcome.partner_2.cta_text') {{ $ctaUrl }}

@lang('welcome.partner_2.closing')

@lang('welcome.signature_closing')
**@lang('welcome.signature_name')**
@lang('welcome.signature_company')
@lang('welcome.signature_phone')
[facturino.mk](https://facturino.mk)
@endcomponent

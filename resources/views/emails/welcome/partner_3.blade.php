@component('mail::message')
@lang('welcome.partner_3.greeting', ['name' => $name])

@lang('welcome.partner_3.hook')

@lang('welcome.partner_3.body')

@lang('welcome.partner_3.features')

@lang('welcome.partner_3.cta_text') {{ $ctaUrl }}

@lang('welcome.partner_3.closing')

@lang('welcome.signature_closing')
**@lang('welcome.signature_name')**
@lang('welcome.signature_company')
@lang('welcome.signature_phone')
[facturino.mk](https://facturino.mk)
@endcomponent

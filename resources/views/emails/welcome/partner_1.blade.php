@component('mail::message')
@lang('welcome.partner_1.greeting', ['name' => $name])

@lang('welcome.partner_1.hook')

@lang('welcome.partner_1.body')

@lang('welcome.partner_1.steps', ['appUrl' => $appUrl])

@lang('welcome.partner_1.cta_text') {{ $appUrl }}

@lang('welcome.partner_1.closing')

@lang('welcome.signature_closing')
**@lang('welcome.signature_name')**
@lang('welcome.signature_company')
@lang('welcome.signature_phone')
[facturino.mk](https://facturino.mk)
@endcomponent

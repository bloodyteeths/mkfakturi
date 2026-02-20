@component('mail::message')
@lang('welcome.company_1.greeting', ['name' => $name])

@lang('welcome.company_1.hook')

@lang('welcome.company_1.body')

@lang('welcome.company_1.steps', ['appUrl' => $appUrl])

@lang('welcome.company_1.cta_text') {{ $appUrl }}

@lang('welcome.company_1.closing')

@lang('welcome.signature_closing')
**@lang('welcome.signature_name')**
@lang('welcome.signature_company')
@lang('welcome.signature_phone')
[facturino.mk](https://facturino.mk)
@endcomponent

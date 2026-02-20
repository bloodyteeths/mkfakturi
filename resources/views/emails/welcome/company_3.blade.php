@component('mail::message')
@lang('welcome.company_3.greeting', ['name' => $name])

@lang('welcome.company_3.hook')

@lang('welcome.company_3.body')

@lang('welcome.company_3.features')

@lang('welcome.company_3.cta_text') {{ $ctaUrl }}

@lang('welcome.company_3.closing')

@lang('welcome.signature_closing')
**@lang('welcome.signature_name')**
@lang('welcome.signature_company')
@lang('welcome.signature_phone')
[facturino.mk](https://facturino.mk)
@endcomponent

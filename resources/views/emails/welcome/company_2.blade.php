@component('mail::message')
@lang('welcome.company_2.greeting', ['name' => $name])

@lang('welcome.company_2.hook')

@lang('welcome.company_2.body')

@lang('welcome.company_2.banks')

@lang('welcome.company_2.cta_text') {{ $ctaUrl }}

@lang('welcome.company_2.closing')

@lang('welcome.signature_closing')
**@lang('welcome.signature_name')**
@lang('welcome.signature_company')
@lang('welcome.signature_phone')
[facturino.mk](https://facturino.mk)
@endcomponent

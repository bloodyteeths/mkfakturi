@component('mail::message')
@lang('welcome.company_4.greeting', ['name' => $name])

@lang('welcome.company_4.hook')

@lang('welcome.company_4.body')

@lang('welcome.company_4.ai_features')

@lang('welcome.company_4.cta_text') {{ $ctaUrl }}

@lang('welcome.company_4.closing')

@lang('welcome.signature_closing')
**@lang('welcome.signature_name')**
@lang('welcome.signature_company')
@lang('welcome.signature_phone')
[facturino.mk](https://facturino.mk)
@endcomponent

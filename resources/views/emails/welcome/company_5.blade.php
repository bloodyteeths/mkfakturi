@component('mail::message')
@lang('welcome.company_5.greeting', ['name' => $name])

@lang('welcome.company_5.hook')

@lang('welcome.company_5.body')

@lang('welcome.company_5.upgrade_nudge')

@lang('welcome.company_5.cta_text') {{ $ctaUrl }}

@lang('welcome.company_5.closing')

@lang('welcome.signature_closing')
**@lang('welcome.signature_name')**
@lang('welcome.signature_company')
@lang('welcome.signature_phone')
[facturino.mk](https://facturino.mk)
@endcomponent

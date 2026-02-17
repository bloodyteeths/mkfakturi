@component('mail::message')
@lang('outreach.initial.greeting')

@lang('outreach.initial.intro')

@lang('outreach.initial.benefit')

@component('mail::button', ['url' => $demoUrl])
@lang('outreach.initial.cta')
@endcomponent

<small style="color: #999;">@lang('outreach.initial.opt_out')</small>

<small><a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a></small>
@endcomponent

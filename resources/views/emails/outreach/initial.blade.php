@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => config('app.url')])
            Facturino
        @endcomponent
    @endslot

    {{-- Body --}}
    @slot('subcopy')
        @component('mail::subcopy')
            <p>@lang('outreach.initial.greeting', ['company' => $companyName])</p>

            <p>@lang('outreach.initial.intro')</p>

            <p>@lang('outreach.initial.benefit')</p>

            @component('mail::button', ['url' => $demoUrl])
                @lang('outreach.initial.cta')
            @endcomponent

            <p style="font-size: 12px; color: #666;">
                @lang('outreach.initial.opt_out')
            </p>
        @endcomponent
    @endslot

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            <a href="{{ $unsubscribeUrl }}" style="color: #999;">@lang('outreach.unsubscribe')</a>
            <br>
            Powered by <a class="footer-link" href="https://facturino.mk" target="_blank">Facturino</a>
        @endcomponent
    @endslot
@endcomponent

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
            <p>@lang('outreach.followup3.greeting')</p>

            <p>@lang('outreach.followup3.trial_offer')</p>

            <p>@lang('outreach.followup3.features')</p>

            <p>@lang('outreach.followup3.pricing')</p>

            @component('mail::button', ['url' => $signupUrl])
                @lang('outreach.followup3.cta')
            @endcomponent

            <p style="font-size: 12px; color: #666;">
                @lang('outreach.followup3.no_card')
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

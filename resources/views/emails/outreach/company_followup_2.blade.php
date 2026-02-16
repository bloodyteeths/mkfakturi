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
            <p>@lang('outreach.company_followup2.greeting', ['company' => $companyName])</p>

            <p>@lang('outreach.company_followup2.trial_offer')</p>

            <p>@lang('outreach.company_followup2.pricing')</p>

            @component('mail::button', ['url' => $signupUrl])
                @lang('outreach.company_followup2.cta')
            @endcomponent
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

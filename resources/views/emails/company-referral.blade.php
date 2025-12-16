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
            <h2>@lang('company_referral.greeting')</h2>

            <p>@lang('company_referral.intro', ['company' => $inviterCompanyName])</p>

            <p>@lang('company_referral.benefits')</p>

            <p>@lang('company_referral.instructions')</p>

            @component('mail::button', ['url' => $signupLink])
                @lang('company_referral.signup_button')
            @endcomponent

            <p>@lang('company_referral.ignore_notice')</p>
        @endcomponent
    @endslot

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            Powered by <a class="footer-link" href="https://facturino.mk" target="_blank">Facturino</a>
        @endcomponent
    @endslot
@endcomponent

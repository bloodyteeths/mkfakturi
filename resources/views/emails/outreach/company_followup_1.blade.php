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
            <p>@lang('outreach.company_followup1.greeting', ['company' => $companyName])</p>

            <p>@lang('outreach.company_followup1.bank_intro')</p>

            <p>@lang('outreach.company_followup1.banks_list')</p>

            @component('mail::button', ['url' => $signupUrl])
                @lang('outreach.company_followup1.cta')
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

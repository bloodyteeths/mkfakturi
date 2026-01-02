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
            <p>@lang('outreach.partner_invite.greeting', ['name' => $partnerName])</p>

            <p>@lang('outreach.partner_invite.congrats')</p>

            <p>@lang('outreach.partner_invite.benefits')</p>

            @component('mail::button', ['url' => $activationUrl])
                @lang('outreach.partner_invite.cta')
            @endcomponent

            <p>@lang('outreach.partner_invite.contact')</p>
        @endcomponent
    @endslot

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            Powered by <a class="footer-link" href="https://facturino.mk" target="_blank">Facturino</a>
        @endcomponent
    @endslot
@endcomponent

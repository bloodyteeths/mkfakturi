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
            <h2>@lang('user_invitation.greeting', ['name' => $user->name])</h2>

            <p>@lang('user_invitation.intro', ['company' => $companyName])</p>

            <p>@lang('user_invitation.instructions')</p>

            @component('mail::button', ['url' => $setPasswordUrl])
                @lang('user_invitation.set_password_button')
            @endcomponent

            <p>@lang('user_invitation.link_expiry')</p>

            <p>@lang('user_invitation.ignore_notice')</p>
        @endcomponent
    @endslot

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            Powered by <a class="footer-link" href="https://facturino.mk" target="_blank">Facturino</a>
        @endcomponent
    @endslot
@endcomponent

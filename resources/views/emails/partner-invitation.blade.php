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
            <h2>@lang('partner_invitation.greeting')</h2>

            <p>@lang('partner_invitation.intro', ['company' => $companyName])</p>

            <p>@lang('partner_invitation.permissions_intro')</p>

            <ul>
                @foreach($permissions as $permission)
                    <li>{{ $permission }}</li>
                @endforeach
            </ul>

            <p>@lang('partner_invitation.instructions')</p>

            @component('mail::button', ['url' => $inviteLink])
                @lang('partner_invitation.accept_button')
            @endcomponent

            <p>@lang('partner_invitation.ignore_notice')</p>
        @endcomponent
    @endslot

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            Powered by <a class="footer-link" href="https://facturino.mk" target="_blank">Facturino</a>
        @endcomponent
    @endslot
@endcomponent

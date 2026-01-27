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
            <h2>@lang('data_export.email_greeting', ['name' => $user->name])</h2>

            <p>@lang('data_export.email_intro')</p>

            <p><strong>@lang('data_export.email_file_size'):</strong> {{ $fileSizeMb }} MB</p>
            <p><strong>@lang('data_export.email_expires'):</strong> {{ $expiresAt }}</p>

            @component('mail::button', ['url' => $downloadUrl])
                @lang('data_export.email_download_button')
            @endcomponent

            <p>@lang('data_export.email_expiry_notice')</p>
        @endcomponent
    @endslot

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            Powered by <a class="footer-link" href="https://facturino.mk" target="_blank">Facturino</a>
        @endcomponent
    @endslot
@endcomponent

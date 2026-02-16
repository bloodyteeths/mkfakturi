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
            <p>@lang('outreach.directory.greeting', ['name' => $contactName])</p>

            <p>@lang('outreach.directory.intro', ['directory' => $directoryName])</p>

            <p>@lang('outreach.directory.description')</p>

            <p><strong>@lang('outreach.directory.features_title')</strong></p>
            <ul>
                <li>@lang('outreach.directory.feature_1')</li>
                <li>@lang('outreach.directory.feature_2')</li>
                <li>@lang('outreach.directory.feature_3')</li>
                <li>@lang('outreach.directory.feature_4')</li>
                <li>@lang('outreach.directory.feature_5')</li>
                <li>@lang('outreach.directory.feature_6')</li>
            </ul>

            <p>@lang('outreach.directory.ask')</p>

            @component('mail::button', ['url' => $websiteUrl])
                @lang('outreach.directory.cta')
            @endcomponent

            <p>@lang('outreach.directory.closing')</p>
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

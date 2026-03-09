@component('mail::layout')
    @slot('header')
        @component('mail::header', ['url' => ''])
        @if($company->logo)
            <img class="header-logo" src="{{ asset($company->logo) }}" alt="{{ $company->name }}">
        @else
            {{ $company->name }}
        @endif
        @endcomponent
    @endslot

    @slot('subcopy')
        @component('mail::subcopy')
            <p>Почитувани {{ $customer->name }},</p>

            <p>Во прилог Ви испраќаме каматна нота (<strong>{{ $noteNumber }}</strong>) за {{ $calculationCount }} неплатени фактури.</p>

            <p>Вкупен износ за плаќање (главнина + камата): <strong>{{ number_format($grandTotal / 100, 2, '.', ',') }} {{ $currencySymbol }}</strong></p>

            <p>Ве молиме извршете ја уплатата во рок од 8 дена од приемот на оваа нота.</p>

            <p>Доколку уплатата е веќе извршена, ве молиме занемарете го ова известување и известете нè.</p>

            <br>

            <p style="color: #666; font-size: 12px;">Со почит,<br>{{ $company->name }}</p>
        @endcomponent
    @endslot

    @slot('footer')
        @component('mail::footer')
            &copy; {{ date('Y') }} {{ $company->name }} преку Facturino
        @endcomponent
    @endslot
@endcomponent

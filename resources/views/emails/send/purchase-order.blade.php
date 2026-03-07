@component('mail::layout')
    {{-- Header --}}
    @slot('header')
        @component('mail::header', ['url' => ''])
        @if($data['company']['logo'])
            <img class="header-logo" src="{{asset($data['company']['logo'])}}" alt="{{$data['company']['name']}}">
        @else
            {{$data['company']['name']}}
        @endif
        @endcomponent
    @endslot

    {{-- Body --}}

    {{-- Subcopy --}}
    @slot('subcopy')
        @component('mail::subcopy')
            {!! $data['body'] !!}

            <table style="width: 100%; border-collapse: collapse; margin-top: 16px;">
                <tr style="background: #f3f4f6;">
                    <th style="text-align: left; padding: 8px; border: 1px solid #e5e7eb; font-size: 13px;">{{ $data['labels']['item'] }}</th>
                    <th style="text-align: right; padding: 8px; border: 1px solid #e5e7eb; font-size: 13px;">{{ $data['labels']['qty'] }}</th>
                    <th style="text-align: right; padding: 8px; border: 1px solid #e5e7eb; font-size: 13px;">{{ $data['labels']['price'] }}</th>
                    <th style="text-align: right; padding: 8px; border: 1px solid #e5e7eb; font-size: 13px;">{{ $data['labels']['total'] }}</th>
                </tr>
                @foreach($data['purchase_order']['items'] as $item)
                <tr>
                    <td style="padding: 8px; border: 1px solid #e5e7eb; font-size: 13px;">{{ $item['name'] }}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #e5e7eb; font-size: 13px;">{{ $item['quantity'] }}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #e5e7eb; font-size: 13px;">{{ number_format($item['price'] / 100, 2) }}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #e5e7eb; font-size: 13px;">{{ number_format($item['total'] / 100, 2) }}</td>
                </tr>
                @endforeach
                <tr style="background: #f9fafb; font-weight: bold;">
                    <td colspan="3" style="text-align: right; padding: 8px; border: 1px solid #e5e7eb; font-size: 13px;">{{ $data['labels']['total'] }}</td>
                    <td style="text-align: right; padding: 8px; border: 1px solid #e5e7eb; font-size: 13px;">{{ number_format($data['purchase_order']['total'] / 100, 2) }}</td>
                </tr>
            </table>

            @if(!empty($data['purchase_order']['notes']))
            <p style="margin-top: 12px; font-size: 13px; color: #6b7280;">
                <strong>{{ $data['labels']['notes'] }}:</strong> {{ $data['purchase_order']['notes'] }}
            </p>
            @endif
        @endcomponent
    @endslot

    {{-- Footer --}}
    @slot('footer')
        @component('mail::footer')
            Powered by <a class="footer-link" href="https://facturino.mk" target="_blank">Facturino</a>
        @endcomponent
    @endslot
@endcomponent

{{-- CASYS QR Payment Code --}}
@if(!empty($casysQrDataUri))
<div style="margin-top: 15px; text-align: center; border-top: 1px solid #eee; padding-top: 10px;">
    <p style="font-size: 10px; color: #666; margin-bottom: 5px;">Платете онлајн / Pay online</p>
    <img src="{{ $casysQrDataUri }}" style="width: 120px; height: 120px;" alt="QR" />
    <p style="font-size: 8px; color: #999; margin-top: 3px;">Скенирајте го QR кодот за плаќање / Scan QR to pay</p>
</div>
@endif

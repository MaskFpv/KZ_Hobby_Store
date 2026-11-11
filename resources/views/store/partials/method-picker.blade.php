{{-- Pilihan metode bayar prototype + bank VA. Param opsional: $selected --}}
@php $selected = old('method', $selected ?? 'qris'); @endphp
<div class="field">
    <label>Metode pembayaran</label>
    <div class="method-grid" data-method-picker>
        @foreach ([
            'qris' => ['bi-qr-code-scan', 'QRIS', 'GoPay, OVO, DANA, m-banking'],
            'va' => ['bi-bank', 'Virtual Account', 'BCA, BNI, BRI, Mandiri'],
            'card' => ['bi-credit-card-2-front', 'Kartu', 'Visa, Mastercard, JCB'],
        ] as $value => [$icon, $label, $hint])
            <label class="method">
                <input type="radio" name="method" value="{{ $value }}" @checked($selected === $value)>
                <span><i class="bi {{ $icon }}"></i><b>{{ $label }}</b><small>{{ $hint }}</small></span>
            </label>
        @endforeach
    </div>
    @error('method')<span class="error">{{ $message }}</span>@enderror
</div>

<div class="field" data-bank-picker>
    <label>Bank Virtual Account</label>
    <div class="bank-grid">
        @foreach (App\Models\OnlinePayment::BANKS as $code => [$name])
            <label class="method">
                <input type="radio" name="bank" value="{{ $code }}" @checked(old('bank', 'bca') === $code)>
                <span>{{ $name }}</span>
            </label>
        @endforeach
    </div>
    @error('bank')<span class="error">{{ $message }}</span>@enderror
</div>

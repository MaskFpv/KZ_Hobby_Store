<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Store & update Stok Masuk. Status hanya dipilih saat membuat data baru. */
class StockInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'supplier' => ['nullable', 'string', 'max:255'],
            'quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'purchase_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'ordered_at' => ['required', 'date', 'before_or_equal:today'],
            'status' => $creating ? ['required', Rule::in(['ordered', 'shipped', 'received'])] : ['prohibited'],
            'update_cost' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'product_id' => 'produk',
            'quantity' => 'jumlah',
            'purchase_price' => 'harga beli satuan',
            'ordered_at' => 'tanggal pesan',
            'notes' => 'catatan',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Http\Controllers\CustomerController;
use App\Models\Customer;
use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * POST  = buat PO baru (+ DP opsional).
 * PUT   = edit PO yang masih berjalan: hanya estimasi tiba & catatan.
 */
class PreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $common = [
            'estimated_arrival' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if (! $this->isMethod('post')) {
            return $common;
        }

        $walkInId = Customer::where('kode_member', CustomerController::WALK_IN_CODE)->value('id');

        return array_merge($common, [
            'estimated_arrival' => ['nullable', 'date', 'after_or_equal:order_date'],
            'customer_id' => ['required', 'integer', 'exists:customers,id', Rule::notIn(array_filter([$walkInId]))],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'unit_price' => ['required', 'numeric', 'min:0', 'max:9999999999999'],
            'order_date' => ['required', 'date', 'before_or_equal:today'],
            'dp_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', Rule::in(array_keys(Transaction::PAYMENT_METHODS))],
        ]);
    }

    public function messages(): array
    {
        return [
            'customer_id.not_in' => 'Pre-Order harus atas nama member (bukan Pelanggan Umum) agar bisa dihubungi saat barang tiba.',
        ];
    }

    public function attributes(): array
    {
        return [
            'customer_id' => 'pelanggan',
            'product_id' => 'produk',
            'quantity' => 'jumlah',
            'unit_price' => 'harga satuan',
            'order_date' => 'tanggal pesan',
            'estimated_arrival' => 'estimasi tiba',
            'dp_amount' => 'DP',
            'payment_method' => 'metode pembayaran',
        ];
    }
}

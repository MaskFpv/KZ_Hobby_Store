<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Store & update Pelanggan/Member. kode_member dibuat otomatis, tidak diinput. */
class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($this->route('customer'))],
            'telepon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'telepon.regex' => 'Nomor telepon hanya boleh angka, spasi, +, -, dan tanda kurung.',
            'email.unique' => 'Email sudah dipakai member lain.',
        ];
    }
}

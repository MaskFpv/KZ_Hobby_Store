<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** Store & update User. Satu user = satu role. Password opsional saat edit. */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creating = $this->isMethod('post');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'password' => [$creating ? 'required' : 'nullable', 'confirmed', Password::min(8)],
            'role' => ['required', 'exists:roles,name'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama', 'role' => 'role'];
    }
}

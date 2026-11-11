<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Halaman akun member (butuh login guard "customer"). */
class AccountController extends Controller
{
    public function index(Request $request): View
    {
        $customer = $request->user('customer');
        app(\App\Services\OrderService::class)->expireOverdue();

        return view('store.account.index', [
            'customer' => $customer,
            'orders' => $customer->orders()->latest('id')->limit(8)->get(),
            'preOrders' => $customer->preOrders()->with('product:id,name,slug')->latest('id')->limit(5)->get(),
            'transactions' => $customer->transactions()->latest('date')->latest('id')->limit(5)->get(),
        ]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($customer->id)],
            'telepon' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'alamat' => ['nullable', 'string', 'max:500'],
        ], [], ['telepon' => 'nomor WhatsApp']);

        $customer->update($data);

        return back()->with('store_success', 'Profil tersimpan.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $customer = $request->user('customer');

        $request->validate([
            'current_password' => ['required', 'current_password:customer'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['current_password' => 'password lama']);

        $customer->forceFill(['password' => $request->password])->save();

        return back()->with('store_success', 'Password berhasil diganti.');
    }
}

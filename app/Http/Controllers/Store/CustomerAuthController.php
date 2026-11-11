<?php

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Support\Captcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Daftar, masuk, keluar untuk pelanggan website (guard "customer"). */
class CustomerAuthController extends Controller
{
    public function captcha(): Response
    {
        return response(Captcha::png(Captcha::generate()), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function showRegister(): View
    {
        return view('store.auth.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'telepon' => ['required', 'string', 'max:30', 'regex:/^[0-9+\-\s()]+$/'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'terms' => ['accepted'],
            'captcha' => ['required', 'string'],
        ], [
            'terms.accepted' => 'Anda harus menyetujui Syarat & Ketentuan.',
            'telepon.regex' => 'Nomor telepon hanya boleh angka, spasi, +, -, dan tanda kurung.',
        ], ['telepon' => 'nomor WhatsApp']);

        if (! Captcha::check($data['captcha'])) {
            throw ValidationException::withMessages(['captcha' => 'Kode captcha salah. Silakan coba kode yang baru.']);
        }

        if (Customer::where('email', $data['email'])->exists()) {
            // Termasuk member yang didaftarkan kasir di toko: aktivasi lewat CS agar akun tidak diambil orang lain
            throw ValidationException::withMessages([
                'email' => 'Email sudah terdaftar. Silakan masuk, atau hubungi toko bila Anda member yang didaftarkan di kasir.',
            ]);
        }

        $customer = Customer::create([
            'nama' => $data['nama'],
            'email' => $data['email'],
            'telepon' => $data['telepon'],
        ]);
        $customer->forceFill([
            'password' => $data['password'],
            'terms_accepted_at' => now(),
        ])->save();

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('store.account')
            ->with('store_success', "Selamat datang, {$customer->nama}! Kode member Anda {$customer->kode_member}.");
    }

    public function showLogin(): View
    {
        return view('store.auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('customer')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('store.account'));
    }

    public function logout(Request $request): RedirectResponse
    {
        // Hanya keluar dari akun pelanggan; sesi staf (bila ada) tidak ikut keluar
        Auth::guard('customer')->logout();
        $request->session()->regenerateToken();

        return redirect()->route('store.home');
    }
}

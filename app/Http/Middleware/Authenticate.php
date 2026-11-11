<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Belum login: staf (/admin/...) ke login panel, pelanggan ke halaman Masuk website.
     */
    protected function redirectTo(Request $request): ?string
    {
        if ($request->expectsJson()) {
            return null;
        }

        return $request->is('admin', 'admin/*') ? route('login') : route('store.login');
    }
}

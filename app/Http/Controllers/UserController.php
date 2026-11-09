<?php

namespace App\Http\Controllers;

use App\Exports\UserExport;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public const ADMIN_ROLE = 'super-admin';

    public function index(): View
    {
        $this->authorize('view-any', User::class);

        $users = User::with('roles')->withCount('transactions')->orderBy('name')->get();

        return view('app.users.index', compact('users'));
    }

    public function create(): View
    {
        $this->authorize('create', User::class);

        return view('app.users.create', ['roles' => Role::orderBy('name')->pluck('name')]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->withSuccess("Akun {$user->name} dibuat.");
    }

    public function show(User $user): RedirectResponse
    {
        return redirect()->route('users.edit', $user);
    }

    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        return view('app.users.edit', ['user' => $user, 'roles' => Role::orderBy('name')->pluck('name')]);
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        // Jangan sampai toko kehilangan admin terakhir / admin mengunci dirinya sendiri
        if ($user->hasRole(self::ADMIN_ROLE) && $request->role !== self::ADMIN_ROLE) {
            if ($user->is($request->user())) {
                return back()->withInput()->with('error', 'Anda tidak bisa melepas role admin dari akun sendiri.');
            }
            if ($this->adminCount() <= 1) {
                return back()->withInput()->with('error', 'Minimal harus ada satu super-admin.');
            }
        }

        $data = ['name' => $request->name, 'email' => $request->email];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);
        $user->syncRoles([$request->role]);

        return redirect()->route('users.index')->withSuccess(__('crud.common.saved'));
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        if ($user->is($request->user())) {
            return redirect()->route('users.index')->with('error', 'Anda tidak bisa menghapus akun sendiri.');
        }
        if ($user->hasRole(self::ADMIN_ROLE) && $this->adminCount() <= 1) {
            return redirect()->route('users.index')->with('error', 'Minimal harus ada satu super-admin.');
        }

        // Riwayat transaksi tetap ada (user_id -> NULL)
        $user->delete();

        return redirect()->route('users.index')->withSuccess(__('crud.common.removed'));
    }

    public function export()
    {
        $this->authorize('view-any', User::class);

        return Excel::download(new UserExport, date('Ymd') . '__User.xlsx');
    }

    public function exportpdf()
    {
        $this->authorize('view-any', User::class);

        $users = User::with('roles')->withCount('transactions')->orderBy('name')->get();

        return Pdf::loadView('app.users.data', compact('users'))->download('user.pdf');
    }

    private function adminCount(): int
    {
        return User::role(self::ADMIN_ROLE)->count();
    }
}

<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Greeting;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** Fase 4: layout, home (sapaan Jepang), dashboard, pengaturan akses, lokalisasi. */
class PanelUiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@gmail.com')->firstOrFail();
        $this->kasir = User::where('email', 'kasir@gmail.com')->firstOrFail();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_app_uses_jakarta_time_and_indonesian_locale(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('id', app()->getLocale());
        $this->assertSame('Januari', Carbon::create(2026, 1, 1)->translatedFormat('F'));
    }

    public function test_greeting_depends_on_time_and_role(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 11, 9, 8, 0, 0, 'Asia/Jakarta'));
        $admin = Greeting::for($this->admin);
        $this->assertSame('おはようございます', $admin['jp']);
        $this->assertSame('Admin-sama', $admin['name']);
        $this->assertSame('オーナー', $admin['title_jp']);

        Carbon::setTestNow(Carbon::create(2026, 11, 9, 19, 30, 0, 'Asia/Jakarta'));
        $kasir = Greeting::for($this->kasir);
        $this->assertSame('Konbanwa', $kasir['romaji']);
        $this->assertSame('Kasir-san', $kasir['name']);
        $this->assertSame('レジ係', $kasir['title_jp']);

        Carbon::setTestNow(Carbon::create(2026, 11, 9, 23, 0, 0, 'Asia/Jakarta'));
        $this->assertSame('Otsukaresama desu', Greeting::for($this->kasir)['romaji']);
    }

    public function test_home_page_per_role(): void
    {
        $this->actingAs($this->admin)->get(route('home'))
            ->assertOk()->assertSee('Admin-sama')->assertSee('Stok Opname')->assertSee('Perlu perhatian');

        $this->actingAs($this->kasir)->get(route('home'))
            ->assertOk()->assertSee('Kasir-san')->assertSee('Buka Kasir')
            ->assertDontSee('Stok Opname'); // menu & aksi cepat admin tidak tampil
    }

    public function test_sidebar_hides_menus_without_permission(): void
    {
        $this->actingAs($this->kasir)->get(route('home'))
            ->assertSee(route('transaction.index'))
            ->assertDontSee(route('users.index'))
            ->assertDontSee(route('stock-opnames.index'));

        $this->actingAs($this->admin)->get(route('home'))
            ->assertSee(route('users.index'))
            ->assertSee(route('stock-opnames.index'));
    }

    public function test_dashboard_and_report_render_and_profit_is_admin_only(): void
    {
        $this->actingAs($this->admin)->get(route('dashboard.index'))->assertOk()->assertSee('Laba kotor');

        $this->actingAs($this->admin)->get(route('transaction.laporan', ['from' => now()->startOfMonth()->toDateString(), 'to' => now()->toDateString()]))
            ->assertOk()->assertSee('Laba kotor');
        $this->get(route('list-export', ['from' => now()->toDateString()]))->assertOk()->assertDownload();

        // Kasir murni penjualan: tanpa dashboard, laporan, dan export
        $this->actingAs($this->kasir)->get(route('dashboard.index'))->assertForbidden();
        $this->get(route('transaction.laporan'))->assertForbidden();
        $this->get(route('list-export'))->assertForbidden();
        $this->get(route('transaction.data', ['payment_method' => 'cash']))->assertOk()->assertDontSee('Export PDF');
    }

    public function test_login_page_and_disabled_public_routes(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('いらっしゃいませ');
        $this->get('/admin/register')->assertNotFound();
        $this->get('/admin/password/reset')->assertNotFound();
        $this->get('/api/user')->assertNotFound();
    }

    public function test_user_management_protections(): void
    {
        $this->actingAs($this->admin);

        // Buat kasir baru: password minimal 8 + konfirmasi
        $this->post(route('users.store'), ['name' => 'Rin', 'email' => 'rin@toko.com', 'password' => '123', 'password_confirmation' => '123', 'role' => 'kasir'])
            ->assertSessionHasErrors('password');
        $this->post(route('users.store'), ['name' => 'Rin', 'email' => 'rin@toko.com', 'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'role' => 'kasir'])
            ->assertRedirect(route('users.index'));
        $rin = User::where('email', 'rin@toko.com')->firstOrFail();
        $this->assertTrue($rin->hasRole('kasir'));

        // Tidak bisa hapus diri sendiri / melepas role admin sendiri / hapus admin terakhir
        $this->delete(route('users.destroy', $this->admin))->assertSessionHas('error');
        $this->put(route('users.update', $this->admin), ['name' => 'Admin Toko', 'email' => 'admin@gmail.com', 'role' => 'kasir'])
            ->assertSessionHas('error');
        $this->assertTrue($this->admin->fresh()->hasRole('super-admin'));

        // Edit tanpa password: password lama tetap
        $hash = $rin->password;
        $this->put(route('users.update', $rin), ['name' => 'Rin Tohsaka', 'email' => 'rin@toko.com', 'role' => 'kasir'])
            ->assertRedirect(route('users.index'));
        $this->assertSame($hash, $rin->fresh()->password);

        $this->delete(route('users.destroy', $rin))->assertSessionHas('success');
        $this->assertModelMissing($rin);
    }

    public function test_super_admin_role_is_locked(): void
    {
        $this->actingAs($this->admin);
        $role = Role::findByName('super-admin');

        $this->put(route('roles.update', $role), ['name' => 'boss', 'permissions' => []])->assertRedirect(route('roles.index'));
        $this->assertSame('super-admin', $role->fresh()->name);
        $this->assertSame(\Spatie\Permission\Models\Permission::count(), $role->fresh()->permissions()->count());

        $this->delete(route('roles.destroy', $role))->assertSessionHas('error');

        // Role yang masih dipakai user tidak bisa dihapus
        $this->delete(route('roles.destroy', Role::findByName('kasir')))->assertSessionHas('error');
    }

    public function test_kasir_cannot_open_access_settings(): void
    {
        $this->actingAs($this->kasir);
        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('roles.index'))->assertForbidden();
        $this->get(route('permissions.index'))->assertForbidden();
    }
}

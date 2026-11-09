<?php

namespace Tests\Feature\Controllers;

use App\Models\User;
use Spatie\Permission\Models\Permission;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PermissionsSeeder::class);

        // super-admin: lolos semua cek izin lewat Gate::before
        $admin = User::factory()->create(['email' => 'admin@admin.com']);
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        $this->withoutExceptionHandling();
    }

    /**
     * @test
     */
    public function it_displays_index_view_with_permissions(): void
    {
        $response = $this->get(route('permissions.index'));

        $response
            ->assertOk()
            ->assertViewIs('app.permissions.index')
            ->assertViewHas('permissions');
    }

    /**
     * @test
     */
    public function it_displays_create_view_for_permission(): void
    {
        $response = $this->get(route('permissions.create'));

        $response->assertOk()->assertViewIs('app.permissions.create');
    }

    /**
     * @test
     */
    public function it_stores_the_permission(): void
    {
        $response = $this->post(route('permissions.store'), [
            'name' => 'list secretaries',
            'roles' => []
        ]);

        $this->assertDatabaseHas('permissions', ['name' => 'list secretaries']);

        $response->assertRedirect(route('permissions.index'));
    }

    /**
     * @test
     */
    public function it_displays_show_view_for_permission(): void
    {
        $permission = Permission::first();

        $response = $this->get(route('permissions.show', $permission));

        // Tidak ada halaman detail: diarahkan ke form edit
        $response->assertRedirect(route('permissions.edit', $permission));
    }

    /**
     * @test
     */
    public function it_displays_edit_view_for_permission(): void
    {
        $permission = Permission::first();

        $response = $this->get(route('permissions.edit', $permission));

        $response
            ->assertOk()
            ->assertViewIs('app.permissions.edit')
            ->assertViewHas('permission');
    }

    /**
     * @test
     */
    public function it_updates_the_permission(): void
    {
        // Permission kustom (bukan bawaan sistem) boleh diganti nama
        $permission = Permission::create(['name' => 'export laporan']);

        $data = [
            'name' => 'list managers',
            'roles' => [],
        ];

        $response = $this->put(route('permissions.update', $permission), $data);

        $this->assertDatabaseHas('permissions', [
            'id' => $permission->id,
            'name' => 'list managers'
        ]);

        $response->assertRedirect(route('permissions.index'));
    }

    /**
     * @test
     */
    public function it_deletes_the_permission(): void
    {
        $permission = Permission::create(['name' => 'export laporan']);

        $response = $this->delete(route('permissions.destroy', $permission));

        $response->assertRedirect(route('permissions.index'));

        $this->assertModelMissing($permission);
    }

    /**
     * @test
     */
    public function it_protects_system_permissions(): void
    {
        $permission = Permission::findByName('list products');

        $this->put(route('permissions.update', $permission), ['name' => 'hacked', 'roles' => []])
            ->assertRedirect(route('permissions.index'));
        $this->assertDatabaseHas('permissions', ['id' => $permission->id, 'name' => 'list products']);

        // super-admin tetap memegang permission walau tidak dicentang
        $this->assertTrue(\Spatie\Permission\Models\Role::findByName('super-admin')->hasPermissionTo('list products'));

        $this->withExceptionHandling()
            ->delete(route('permissions.destroy', $permission))
            ->assertSessionHas('error');
        $this->assertModelExists($permission);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Panel staf ada di /admin; tamu diarahkan ke login staf. */
    public function test_admin_panel_requires_login(): void
    {
        $this->get('/admin/home')->assertRedirect('/admin/login');
        $this->get('/admin/login')->assertOk();
    }
}

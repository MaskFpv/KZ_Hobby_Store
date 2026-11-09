<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /** Tanpa frontend publik: root diarahkan ke /home, tamu diarahkan ke login. */
    public function test_root_redirects_guest_to_login(): void
    {
        $this->get('/')->assertRedirect('/home');
        $this->get('/home')->assertRedirect('/login');
    }
}

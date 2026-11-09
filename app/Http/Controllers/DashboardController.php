<?php

namespace App\Http\Controllers;

class DashboardController extends Controller
{
    public function index()
    {
        $this->authorize('view-dashboard');

        return view('app.dashboard.index');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;

class WelcomeController extends Controller
{
    /**
     * Halaman sambutan publik sebelum masuk. Pengguna yang sudah login
     * tidak membutuhkan halaman ini dan langsung dibawa ke dashboard.
     */
    public function __invoke()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return view('welcome.index');
    }
}

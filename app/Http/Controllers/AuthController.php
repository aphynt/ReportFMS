<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    //
    public function login()
    {
        return view('auth.login');
    }

    public function login_post(Request $request)
    {
        $credentials = $request->only('nik', 'password');

        if (Auth::attempt($credentials, $request->has('remember'))) {
            if (Auth::user()->statusenabled == true) {
                return redirect()->route('payload.ex.index')->with('alert', 'Selamat Datang');
            } else {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                return redirect()->back()->with('info', 'Akun Anda tidak diaktifkan.');
            }
        }

        return redirect()->back()->with('login', 'NIK atau password salah');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda telah berhasil keluar');
    }
}

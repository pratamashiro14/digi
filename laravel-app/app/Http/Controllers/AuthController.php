<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return back()->with('error', 'Email atau password salah!');
        }

        // Check password - support both MD5 (old project) and Bcrypt (new)
        $passwordValid = false;
        
        // Try MD5 first (old project format)
        if (md5($request->password) === $user->password) {
            $passwordValid = true;
        } 
        // Try plain text comparison (for testing)
        elseif ($request->password === $user->password) {
            $passwordValid = true;
        }
        // Try Bcrypt (new format)
        elseif (strlen($user->password) === 60 && Hash::check($request->password, $user->password)) {
            $passwordValid = true;
        }

        if (!$passwordValid) {
            return back()->with('error', 'Email atau password salah!');
        }

        if ($user->role === 'designer') {
            return back()->with('error', 'Akun ini adalah desainer. Silakan login di menu desainer!');
        }

        Auth::login($user);
        session(['user_id' => $user->id_user, 'nama' => $user->nama]);

        return redirect()->route('home')->with('success', 'Login berhasil!');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'email' => 'required|email|unique:t_user,email',
            'password' => 'required|min:6|confirmed',
        ]);

        User::create([
            'nama' => $request->nama,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'pelanggan',
            'status' => 'aktif',
            'foto' => '',
        ]);

        return redirect()->route('login')->with('success', 'Akun berhasil dibuat! Silakan login.');
    }

    public function logout()
    {
        Auth::logout();
        session()->flush();
        return redirect()->route('home')->with('success', 'Logout berhasil!');
    }
}

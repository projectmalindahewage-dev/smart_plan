<?php
namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
class WebAuthController extends Controller {
 public function showRegistrationForm(): View { return view('auth.register'); }
 public function register(Request $request): RedirectResponse { $data=$request->validate(['name'=>['required','string','max:255'],'email'=>['required','string','email','max:255','unique:users,email'],'password'=>['required','confirmed',Password::defaults()]]); $user=User::create(['name'=>$data['name'],'email'=>$data['email'],'password'=>Hash::make($data['password'])]); Auth::login($user); $request->session()->regenerate(); return redirect()->route('dashboard'); }
 public function showLoginForm(): View { return view('auth.login'); }
 public function login(Request $request): RedirectResponse { $credentials=$request->validate(['email'=>['required','email'],'password'=>['required','string']]); if (! Auth::attempt($credentials,$request->boolean('remember'))) { return back()->withErrors(['email'=>'The provided credentials do not match our records.'])->onlyInput('email'); } $request->session()->regenerate(); return redirect()->intended(route('dashboard')); }
 public function logout(Request $request): RedirectResponse { Auth::logout(); $request->session()->invalidate(); $request->session()->regenerateToken(); return redirect()->route('login'); }
}
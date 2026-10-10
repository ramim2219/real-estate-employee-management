<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function create(): View
    {
        return view('rems.auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ]);

        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Email or password is incorrect.'])->onlyInput('email');
        }

        $user = $request->user();
        if ($user->employee && ! in_array($user->employee->status, ['active','on_leave'], true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return back()->withErrors(['email' => 'This employee account is inactive.'])->onlyInput('email');
        }

        $request->session()->regenerate();
        return redirect()->intended(route('rems.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

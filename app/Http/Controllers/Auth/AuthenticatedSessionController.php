<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            //'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
            'errors' => session()->get('errors')?->getBag('default')?->toArray(),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        // Set up a single validation to make sure one field is filled
        $request->validate([
            'email' => 'required_without:phone',
            'phone' => 'required_without:email',
        ], [
            'email.required_without' => 'Please provide an email.',
            'phone.required_without' => 'Please provide a phone number.',
        ]);

        // Define validation rules based on which field is filled
        $rules = [
            'password' => 'required',
            'remember' => 'boolean'
        ];
        if ($request->filled('email')) {
            $rules['email'] = 'email'; 
        } else {
            $rules['phone'] = 'regex:/^\+?[1-9]\d{1,14}$/'; 
        }

        $request->validate($rules);

        // Determine the login field used
        $loginField = $request->filled('email') ? 'email' : 'phone';
        $credentials = [
            $loginField => $request->input($loginField),
            'password' => $request->password,
        ];

        // Attempt to authenticate
        if (!Auth::attempt($credentials, $request->filled('remember'))) {
            return back()->withErrors([
                'login' => 'The provided credentials do not match our records.',
            ])->withInput();
        }

        $request->session()->regenerate();
        return redirect()->intended('/dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}

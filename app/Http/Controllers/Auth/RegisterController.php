<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class RegisterController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('auth/Register');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'min:2', 'max:255'],
            'last_name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email:strict',
                'max:255',
                Rule::unique(User::class),
            ],
            // NOTE: the password rules are set in AppServiceProvider::boot(), shared with resetting a password.
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create($validated);

        // Sends the confirmation email (Laravel listens for Registered, as User must verify its email).
        event(new Registered($user));

        Auth::login($user);

        return to_route('verification.notice');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RegistrationController extends Controller
{
    /**
     * Show the registration form
     */
    public function show()
    {
        return view('home.index');
    }

    /**
     * Handle user registration
     */
    public function store(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
        ]);

        // Hash the password
        $validated['password'] = bcrypt($validated['password']);

        // Set default role as 'user' for new registrations
        $validated['role'] = 'user';

        // Create the user
        $user = User::create($validated);

        // Automatically log in the user
        Auth::login($user);

        // Regenerate session for security
        $request->session()->regenerate();

        // Redirect to dashboard with success message
        return redirect()->route('dashboard')->with('success', 'Registration successful! Welcome to your dashboard.');
    }
}

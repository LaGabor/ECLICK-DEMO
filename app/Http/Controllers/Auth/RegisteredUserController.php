<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\AuthPassword;
use App\Support\UserRole;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:32', 'regex:/^[\d\s\-\+\(\)]+$/'],
            'bank_account' => ['required', 'string', 'max:64'],
            'password' => ['required', 'confirmed', AuthPassword::rule()],
            'terms_accepted' => ['accepted'],
        ]);

        if (User::query()
            ->where(function ($query) use ($validated): void {
                $query->where('email', $validated['email'])
                    ->orWhere('phone', $validated['phone'])
                    ->orWhere('bank_account', $validated['bank_account']);
            })
            ->exists()) {
            throw ValidationException::withMessages([
                'registration' => [__('messages.registration_duplicate')],
            ]);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'bank_account' => $validated['bank_account'],
            'password' => Hash::make($validated['password']),
            'terms_accepted_at' => now(),
        ]);

        $user->assignRole(Role::findOrCreate(UserRole::User->value, 'web'));

        event(new Registered($user));

        return redirect()
            ->route('login')
            ->with('status', __('messages.registration_success'));
    }
}

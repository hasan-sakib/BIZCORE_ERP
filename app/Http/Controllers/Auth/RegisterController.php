<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegister(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect('/dashboard');
        }

        return view('auth.register', [
            'roles'    => Role::where('slug', '!=', 'super_admin')->orderBy('name')->get(),
            'branches' => Branch::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'             => ['required', 'string', 'max:150'],
            'email'            => ['required', 'email', 'max:150', 'unique:users,email'],
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
            'role_id'          => ['required', 'integer', 'exists:roles,id'],
            'branch_id'        => ['required', 'integer', 'exists:branches,id'],
        ]);

        // Prevent assigning super_admin role via self-registration
        abort_if(
            Role::where('id', $data['role_id'])->where('slug', 'super_admin')->exists(),
            403, 'Cannot register as super admin.'
        );

        User::create([
            'branch_id' => $data['branch_id'],
            'role_id'   => $data['role_id'],
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => Hash::make($data['password']),
            'status'    => UserStatus::Inactive,
        ]);

        return redirect('/login')->with(
            'success',
            'Your account has been created and is pending admin approval. You\'ll be notified when it\'s activated.'
        );
    }
}

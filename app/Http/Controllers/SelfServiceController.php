<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * Self-service settings (Q-021): a read-only user MAY change their OWN password/contact.
 * These routes are whitelisted in the write-guard; all OTHER mutations stay blocked.
 */
class SelfServiceController extends Controller
{
    public function editPassword(): View
    {
        return view('self-service.password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        if (! Hash::check($data['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.']);
        }

        // The User model's 'hashed' cast hashes the new password on save.
        $user->update(['password' => $data['password']]);

        return back()->with('status', 'Password berhasil diperbarui.');
    }
}

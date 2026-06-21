<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class MfaController extends Controller
{
    public function showChallenge(): View|RedirectResponse
    {
        if (!Auth::check()) {
            return redirect('/login');
        }

        if (session('mfa_verified')) {
            return redirect()->intended('/dashboard');
        }

        return view('auth.mfa');
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string', 'size:6']]);

        $user = Auth::user();

        if (!$user->two_factor_enabled || empty($user->two_factor_secret)) {
            session(['mfa_verified' => true]);
            return redirect()->intended('/dashboard');
        }

        $code   = (string) $request->input('code');
        $secret = $user->two_factor_secret;

        if (!$this->verifyTotp($secret, $code)) {
            return back()->withErrors(['code' => 'The provided two-factor code is invalid.']);
        }

        session(['mfa_verified' => true]);

        return redirect()->intended('/dashboard');
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $request->user()->update([
            'two_factor_enabled' => false,
            'two_factor_secret'  => null,
        ]);

        session()->forget('mfa_verified');

        return back()->with('success', 'Two-factor authentication has been disabled.');
    }

    private function verifyTotp(string $secret, string $code): bool
    {
        $timeSlot = (int) floor(time() / 30);

        // Check current and adjacent windows (±1) to allow clock drift
        for ($offset = -1; $offset <= 1; $offset++) {
            if ($this->generateTotp($secret, $timeSlot + $offset) === $code) {
                return true;
            }
        }

        return false;
    }

    private function generateTotp(string $secret, int $timeSlot): string
    {
        $secretBytes = $this->base32Decode($secret);
        $time        = pack('N*', 0) . pack('N*', $timeSlot);
        $hash        = hash_hmac('sha1', $time, $secretBytes, true);
        $offset      = ord($hash[19]) & 0x0F;
        $code        = (
            ((ord($hash[$offset])     & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) <<  8) |
            ((ord($hash[$offset + 3]) & 0xFF))
        ) % 1_000_000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode(string $base32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32   = strtoupper($base32);
        $binary   = '';

        foreach (str_split($base32) as $char) {
            $pos     = strpos($alphabet, $char);
            if ($pos === false) {
                continue;
            }
            $binary .= str_pad(decbin($pos), 5, '0', STR_PAD_LEFT);
        }

        $bytes = '';
        foreach (str_split($binary, 8) as $chunk) {
            if (strlen($chunk) === 8) {
                $bytes .= chr(bindec($chunk));
            }
        }

        return $bytes;
    }
}

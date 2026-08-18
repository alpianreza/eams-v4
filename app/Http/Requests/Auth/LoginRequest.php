<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\AuthAudit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /** Valid bcrypt string so Hash::check does real work even for unknown users (anti timing/enumeration, BR-40). */
    protected const DUMMY_HASH = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string'],   // username OR email
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Validate credentials (username OR email + bcrypt password) with throttling (BR-40).
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $login = (string) $this->input('login');
        $user = $this->findUser($login);

        // Constant-time: always run a bcrypt comparison even when the user is unknown.
        $passwordOk = Hash::check((string) $this->input('password'), $user?->password ?? self::DUMMY_HASH);

        if (! $user || ! $user->isActive() || ! $passwordOk) {
            RateLimiter::hit($this->throttleKey());
            AuthAudit::failed($this, $login);

            throw ValidationException::withMessages([
                'login' => 'Kredensial tidak cocok dengan data kami.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    protected function findUser(string $login): ?User
    {
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        return User::query()->where($field, $login)->first();
    }

    /**
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        throw ValidationException::withMessages([
            'login' => 'Terlalu banyak percobaan login. Coba lagi dalam satu menit.',
        ]);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('login')).'|'.$this->ip());
    }
}

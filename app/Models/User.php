<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'username', 'email', 'password', 'photo',
        'role', 'permission', 'page_access', 'status', 'wa_number',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'page_access' => 'array',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Authorization helpers (BR-41: three-layer role + permission + page_access)
    |--------------------------------------------------------------------------
    */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function hasRole(string|array $roles): bool
    {
        return in_array($this->role, (array) $roles, true);
    }

    /** permission = 'write' grants mutation; 'read' is read-only (BR-42). */
    public function hasWriteAccess(): bool
    {
        return $this->permission === 'write';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Menu/page visibility follows page_access; admin sees everything (BR-44). */
    public function canAccessPage(string $page): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        $access = $this->page_access ?? [];

        return in_array($page, $access, true);
    }
}

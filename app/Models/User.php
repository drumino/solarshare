<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLES = ['admin' => 'Administrateur', 'user' => 'Utilisateur'];

    protected $fillable = ['name', 'email', 'password', 'role', 'phone', 'city'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /** Vrai si cet utilisateur est le seul administrateur restant. */
    public function isLastAdmin(): bool
    {
        return $this->isAdmin() && static::where('role', 'admin')->count() === 1;
    }
}

<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasRoles;
    use Notifiable;
    use HasFactory;
    use Searchable;

    protected $fillable = ['name', 'email', 'password'];

    protected $searchableFields = ['*'];

    protected $hidden = ['password'];

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super-admin');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}

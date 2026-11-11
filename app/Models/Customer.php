<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Pelanggan / member. Bisa login ke website lewat guard "customer"
 * bila punya password (daftar sendiri di website).
 */
class Customer extends Authenticatable
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['kode_member', 'nama', 'email', 'telepon', 'alamat'];

    protected $searchableFields = ['kode_member', 'nama', 'email', 'telepon'];

    protected $hidden = ['password', 'remember_token'];

    protected $casts = [
        'password' => 'hashed',
        'terms_accepted_at' => 'datetime',
    ];

    public function hasWebAccount(): bool
    {
        return ! empty($this->password);
    }

    protected static function booted(): void
    {
        static::creating(function (Customer $customer) {
            if (empty($customer->kode_member)) {
                $customer->kode_member = static::generateKodeMember();
            }
        });
    }

    public static function generateKodeMember(): string
    {
        $next = ((int) static::max('id')) + 1;

        do {
            $kode = 'MBR' . str_pad((string) $next++, 5, '0', STR_PAD_LEFT);
        } while (static::where('kode_member', $kode)->exists());

        return $kode;
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function preOrders()
    {
        return $this->hasMany(PreOrder::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}

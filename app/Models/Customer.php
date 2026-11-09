<?php

namespace App\Models;

use App\Models\Scopes\Searchable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    use Searchable;

    protected $fillable = ['kode_member', 'nama', 'email', 'telepon', 'alamat'];

    protected $searchableFields = ['kode_member', 'nama', 'email', 'telepon'];

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
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentProvider extends Model
{
    protected $table = 'pi_provider';

    protected $fillable = [
        'provider_name',
        'provider_code',
        'image_url',
        'terms_conditions',
        'status',
    ];

    public function details()
    {
        return $this->hasMany(InstallmentProviderDetail::class, 'pi_provider_id');
    }

    public function orders()
    {
        return $this->hasMany(InstallmentOrder::class, 'pi_provider_id');
    }
}

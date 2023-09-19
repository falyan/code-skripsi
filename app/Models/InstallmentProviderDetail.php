<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallmentProviderDetail extends Model
{
    protected $table = 'pi_provider_detail';

    protected $fillable = [
        'pi_provider_id',
        'tenor',
        'mdr_percentage',
        'fee_percentage',
    ];

    public function provider()
    {
        return $this->belongsTo(InstallmentProvider::class, 'pi_provider_id');
    }

}

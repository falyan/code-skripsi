<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UbahDayaMaster extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'master_ubah_daya';

    protected $with = [];

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['id'];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * @var void Relations
     */
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }


}

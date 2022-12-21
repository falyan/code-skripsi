<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;

class ServiceLog extends Model
{
    /**
     * @var string The database table used by the model.
     */
    protected $table = 'service_logs';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['id'];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $casts = [
        'req' => AsArrayObject::class,
        'res' => AsArrayObject::class
    ];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = ['req', 'res'];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = ['req'];
}

<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MasterData extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'master_data';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'type',
        'key',
        'value_type',
        'value',
        'created_by',
        'updated_by',
        'parent_id',
    ];

    /**
     * @var array Validation rules for attributes
     */
    public $rules = [];

    /**
     * @var array Attributes to be cast to native types
     */
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = [];

    /**
     * @var array Attributes to be appended to the API representation of the model (ex. toArray())
     */
    protected $appends = [];

    /**
     * @var array Attributes to be removed from the API representation of the model (ex. toArray())
     */
    protected $hidden = [];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [];
    public $hasMany = [];
    public $belongsTo = [];
    public $belongsToMany = [];
    public $morphTo = [];
    public $morphOne = [];
    public $morphMany = [];
    public $attachOne = [];
    public $attachMany = [];
    protected function serializeDate($date){
        return $date->format('Y-m-d H:i:s');
    }


    public function scopeSearch($query, $searchTerm)
    {
        return $query->where('name', 'ILIKE', "%{$searchTerm}%");
    }

    public function scopeSearchByKey($query, $key)
    {
        return $query->where('key', 'ILIKE', "%{$key}%");
    }

    public function parent()
    {
        return $this->belongsTo(MasterData::class, 'parent_id');
    }

    public function child()
    {
        return $this->hasMany(MasterData::class, 'parent_id');
    }

    public function product(){
        return $this->hasMany(Product::class);
    }

    public function variants()
    {
        return $this->hasMany(Variant::class, 'category_id');
    }

    public function mdr_category_active(){
        return $this->hasOne(MdrCategory::class)->where('status', 1);
    }

    public function mdr_merchant_active(){
        return $this->hasOne(MdrMerchant::class)->where('status', 1);
    }
}

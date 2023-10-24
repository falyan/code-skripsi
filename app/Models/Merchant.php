<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Merchant extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string The database table used by the model.
     */
    protected $table = 'merchant';

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
     * @var array Attributes to be cast to native types
     */
    protected $casts = [
        'can_credit' => 'boolean',
        'is_npwp_required' => 'boolean',
    ];

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
        'deleted_at',
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

    /**
     * @var void Relations
     */
    protected function serializeDate($date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    public function etalase()
    {
        return $this->hasMany(Etalase::class);
    }

    public function operationals()
    {
        return $this->hasMany(MerchantOperationalHour::class);
    }

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function corporate()
    {
        return $this->belongsTo(Corporate::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function expedition()
    {
        return $this->hasOne(MerchantExpedition::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class);
    }

    public function review()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function mdr_merchant_active()
    {
        return $this->hasOne(MdrMerchant::class)->where('status', 1);
    }

    public function discussion_master()
    {
        return $this->hasMany(DiscussionMaster::class, 'merchant_id');
    }

    public function discussion_response()
    {
        return $this->hasMany(DiscussionResponse::class, 'merchant_id');
    }

    public function official_store()
    {
        return $this->hasOne(MasterEvStore::class);
    }

    public function banner()
    {
        return $this->hasMany(MerchantBanner::class, 'merchant_id', 'id')->where('status', 1);
    }

    public function promo_merchant()
    {
        return $this->hasMany(PromoMerchant::class, 'merchant_id')->where('status', 1);
    }

    public function mitra()
    {
        return $this->belongsTo(AgentMasterMitra::class);
    }
}

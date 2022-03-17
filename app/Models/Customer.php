<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'customer';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['id'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'full_name',
        'username',
        'email',
        'phone',
        'type',
        'role_id',
        'merchant_id',
        'status',
        'related_pln_mobile_customer_id',
        'created_by',
        'updated_by'
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
    protected $hidden = [
        'username',
        'password'
    ];

    /**
     * @var array Attributes to be cast to Argon (Carbon) instances
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at'
    ];
    protected function serializeDate($date){
        return $date->format('Y-m-d H:i:s');
    }


    /**
     * @var array Relations
     */
    public function iconcash()
    {
        return $this->hasOne(IconcashCredential::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class, 'buyer_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'buyer_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function customerAddress()
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function discussion_master()
    {
        return $this->hasMany(DiscussionMaster::class, 'customer_id');
    }

    public function discussion_response()
    {
        return $this->hasMany(DiscussionResponse::class, 'customer_id');
    }

    /**
     * @var void Custom Static Functions
     */

    public static function findByrelatedCustomerId($related_customer_id)
    {
        $user = static::where('related_pln_mobile_customer_id', $related_customer_id)->first();
        return $user;
    }
}

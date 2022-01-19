<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'product';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['id'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'merchant_id',
        'name',
        'price',
        'strike_price',
        'minimum_purchase',
        'category_id',
        'etalase_id',
        'condition',
        'weight',
        'description',
        'is_shipping_insurance',
        'shipping_service',
        'is_featured_product',
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

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function product_stock(){
        return $this->hasMany(ProductStock::class);
    }

    public function stock_active()
    {
        return $this->hasOne(ProductStock::class)->where('status',1);
    }

    public function product_photo(){
        return $this->hasMany(ProductPhoto::class);
    }

    public function cart_detail(){
            return $this->hasMany(CartDetail::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function is_wishlist(){
        return $this->hasOne(Wishlist::class)->where('customer_id', Auth::id());
    }

    public function order_details()
    {
        return $this->hasMany(OrderDetail::class, 'product_id');
    }

    public function etalase(){
        return $this->belongsTo(Etalase::class);
    }

    public function category(){
        return $this->belongsTo(MasterData::class);
    }
}

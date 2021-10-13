<?php
namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    /**
     * @var string The database table used by the model.
     */
    public $table = 'cart';

    /**
     * @var array Guarded fields
     */
    protected $guarded = ['*'];

    /**
     * @var array Fillable fields
     */
    protected $fillable = [
        'buyer_id',
        'related_pln_mobile_customer_id',
        'merchant_id',
        'merchant_id'
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
    
    public function cart_detail()
    {
        return $this->hasMany(CartDetail::class, 'cart_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function merchants()
    {
        return $this->belongsTo(Merchant::class, 'merchant_id');
    }

    /**
     * @var array Custom Static Functions
     */

    public static function findByRelatedId($buyer_id, $related_customer_id)
    {
        $cart = static::whereNotNull('buyer_id')->where('buyer_id', $buyer_id)->orWhere('related_pln_mobile_customer_id', $related_customer_id)->first();
        throw_if(!$cart, new Exception('List cart tidak ditemukan'));
        return $cart;
    }
}

<?php

namespace App\Models;

use App\Http\Services\Manager\IconcashManager;
use Exception;
use Illuminate\Database\Eloquent\Model;

class IconcashInquiry extends Model
{
    /**
     * @var string The database table used by the model.
     */
    public $table = 'iconcash_inquiry';

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
    protected $casts = [];

    /**
     * @var array Attributes to be cast to JSON
     */
    protected $jsonable = ['value'];

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
     * @var void Relations
     */

    public function iconcash()
    {
        return $this->belongsTo(IconcashCredential::class);
    }

    /**
     * @var void Custom Static Functions
     */

    public static function createWithdrawalInquiry($iconcash, $bank_account_name, $bank_account_no, $bank_id, $nominal, $source_account_id)
    {
        try {
            $model = new self;

            $response = IconcashManager::withdrawalInquiry($iconcash->token, $bank_account_name, $bank_account_no, $bank_id, $nominal, $source_account_id);

            $model->create([
                'customer_id' => $iconcash->customer_id,
                'iconcash_id' => $iconcash->id,
                'type' => 'withdrawal',
                'source_account_id' => $source_account_id,
                'amount' => $nominal,
                'iconcash_order_id' => $response->orderId,
                'res_json' => json_encode($response),
            ]);

            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public static function createWithdrawalInquiryV2($iconcash, $bank_account_name, $bank_account_no, $bank_id, $nominal, $source_account_id)
    {
        try {
            $model = new self;

            $response = IconcashManager::withdrawalInquiryV2($iconcash->token, $bank_account_name, $bank_account_no, $bank_id, $nominal, $source_account_id);

            $model->create([
                'customer_id' => $iconcash->customer_id,
                'iconcash_id' => $iconcash->id,
                'type' => 'withdrawal',
                'source_account_id' => $source_account_id,
                'amount' => $nominal,
                'iconcash_order_id' => data_get($response, 'orderId'),
                'res_json' => json_encode($response),
            ]);

            return $response;
        } catch (Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
    }

    public static function createTopupInquiry($iconcash, $account_type_id, $amount, $client_ref, $corporate_id, $order, $type = 'topup')
    {
        $model = new self;
        $response = IconcashManager::topupInquiry($iconcash->phone, $account_type_id, $amount, $client_ref, $corporate_id);

        $model->create([
            'customer_id' => $iconcash->customer_id,
            'iconcash_id' => $iconcash->id,
            'type' => $type,
            'source_account_id' => $response->accountId,
            'order_id' => $order->id,
            'amount' => $amount,
            'client_ref' => $client_ref,
            'iconcash_order_id' => $response->orderId,
            'res_json' => json_encode($response),
        ]);

        return $response;
    }

    public static function updateTopupInquiry($id, $iconcash, $account_type_id, $amount, $client_ref, $corporate_id)
    {
        // update inquiry
        $model = self::where('id', $id)->first();

        $response = IconcashManager::topupInquiry($iconcash->phone, $account_type_id, $amount, $client_ref, $corporate_id);

        $model->update([
            'source_account_id' => $response->accountId,
            'iconcash_order_id' => $response->orderId,
            'res_json' => json_encode($response),
        ]);

        return $response;
    }

    public static function createTopupDepositInquiry($iconcash, $amount, $client_ref, $pspId, $order)
    {
        $model = new self;

        $response = IconcashManager::topupDeposit($iconcash->token, $amount, $client_ref, $pspId);
        $model->create([
            'customer_id' => $iconcash->customer_id,
            'iconcash_id' => $iconcash->id,
            'type' => 'topup',
            'source_account_id' => $response->accountId,
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'client_ref' => $client_ref,
            'iconcash_order_id' => $response->orderId,
            'res_json' => json_encode($response),
        ]);

        return $response;
    }
}

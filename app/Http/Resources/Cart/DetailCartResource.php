<?php

namespace App\Http\Resources\Cart;

use App\Models\Merchant;
use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

class DetailCartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = $this->resource;
        $resource = (object) $resource->toArray();
        $cart_detail = data_get($resource, 'cart_detail');
        
        $new_array = array();
        foreach ($cart_detail as $cart) {
            if (!isset($cart['related_merchant_id'])) {
                $new_array[$cart['related_merchant_id']] = array();
            }
            
            $cart['product']['quantity'] = $cart['quantity'];
            $new_array[$cart['related_merchant_id']][] = $cart['product'];
        }
        
        // dd(gettype(data_get($resource, 'cart_detail')));
        return [
            'id' => data_get($resource, 'id'),
            'buyer_id' => data_get($resource, 'buyer_id'),
            'related_pln_mobile_customer_id' => data_get($resource, 'related_pln_mobile_customer_id'),
            'created_at' => data_get($resource, 'created_at'),
            'updated_at' => data_get($resource, 'updated_at'),
            'cart_detail' => array_map(function($products) {
                    return [
                        'merchant' => $products[0]['merchant'],
                        'products' => $products,
                    ];
                }, $new_array)
        ];
    }
}

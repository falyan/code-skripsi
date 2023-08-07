<?php

namespace App\Http\Resources\Rajaongkir;

use App\Models\MasterData;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class RajaongkirSameLogisticResources extends JsonResource
{
    protected $imageCourier;

    public function toArray($request)
    {
        $master_data = Cache::remember('master_data', 60 * 60 * 24, function () {
            return MasterData::where('key', 'ro_courier')->get();
        });
        $this->imageCourier = $master_data;

        return array_map(function ($courier) {
            return [
                'code' => data_get($courier, 'code'),
                'name' => data_get($courier, 'name'),
                // 'delivery_discount' => array_merge($this->delivery_discount->toArray(), [
                //     'discount_amount' => (int) $this->delivery_discount->discount_amount
                // ]),
                'image' => $this->getImageCourier(data_get($courier, 'code')),
                'data' => array_map(function ($ongkir) {
                    $etd = $this->getNumber($ongkir->cost[0]->etd);
                    $estimate_day = '';
                    if (count($etd) > 1) {
                        $estimate_day = $etd[0] . ' - ' . $etd[1] . ' Day';
                    } else {
                        $estimate_day =  $etd[0] == '' ? '1 Day' : $etd[0] . ' Day';
                    }
                    return [
                        'service_code' => null,
                        'service_name' => data_get($ongkir, 'service'),
                        // 'description' => data_get($ongkir, 'description'),
                        'estimate_day' => $estimate_day,
                        'price' => $ongkir->cost[0]->value,
                        'min_weight' => 0,
                        'max_weight' => 0,
                    ];
                }, $courier->costs)
            ];
        }, $this->rajaongkir->results);

        // return [
        //     'asal_pengiriman' => [
        //         'district_id' => $this->rajaongkir->origin_details->subdistrict_id,
        //         'district_name' => $this->rajaongkir->origin_details->subdistrict_name,
        //         'city_id' => $this->rajaongkir->origin_details->city_id,
        //         'city_name' => $this->rajaongkir->origin_details->city,
        //         'province_id' => $this->rajaongkir->origin_details->province_id,
        //         'province_name' => $this->rajaongkir->origin_details->province,
        //     ],
        //     'tujuan_pengiriman' => [
        //         'district_id' => $this->rajaongkir->destination_details->subdistrict_id,
        //         'district_name' => $this->rajaongkir->destination_details->subdistrict_name,
        //         'city_id' => $this->rajaongkir->destination_details->city_id,
        //         'city_name' => $this->rajaongkir->destination_details->city,
        //         'province_id' => $this->rajaongkir->destination_details->province_id,
        //         'province_name' => $this->rajaongkir->destination_details->province,
        //     ],
        //     'couriers' => array_map(function ($courier) {
        //         return [
        //             'courier_code' => data_get($courier, 'code'),
        //             'courier_name' => data_get($courier, 'name'),
        //             'delivery_discount' => array_merge($this->delivery_discount->toArray(), [
        //                 'discount_amount' => (int) $this->delivery_discount->discount_amount
        //             ]),
        //             'list_ongkir' => array_map(function($ongkir) {
        //                 return [
        //                     'service_name' => data_get($ongkir, 'service'),
        //                     'description' => data_get($ongkir, 'description'),
        //                     'ongkir' => array_map(function ($ongkir_detail) {
        //                         return [
        //                             'harga' => data_get($ongkir_detail, 'value'),
        //                             'estimate_day' => data_get($ongkir_detail, 'etd'),
        //                             'note' => data_get($ongkir_detail, 'note'),
        //                         ];
        //                     }, $ongkir->cost)
        //                 ];
        //             }, $courier->costs)
        //         ];
        //     }, $this->rajaongkir->results),
        // ];
    }

    private function getNumber($string)
    {
        $number = preg_replace('/[^0-9]/', '', $string);
        return str_split($number);
    }

    private function getImageCourier($code = null)
    {
        if ($code == 'J&T') $code = 'jnt';
        $image = collect($this->imageCourier)->where('reference_third_party_id', $code)->first();

        return $image ? $image->photo_url : null;
    }
}

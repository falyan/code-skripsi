<?php

namespace App\Http\Resources\Rajaongkir;

use Illuminate\Http\Resources\Json\JsonResource;

class RajaongkirResources extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        return [
            'asal_pengiriman' => [
                'district_id' => $this->rajaongkir->origin_details->subdistrict_id,
                'district_name' => $this->rajaongkir->origin_details->subdistrict_name,
                'city_id' => $this->rajaongkir->origin_details->city_id,
                'city_name' => $this->rajaongkir->origin_details->city,
                'province_id' => $this->rajaongkir->origin_details->province_id,
                'province_name' => $this->rajaongkir->origin_details->province,
            ],
            'tujuan_pengiriman' => [
                'district_id' => $this->rajaongkir->destination_details->subdistrict_id,
                'district_name' => $this->rajaongkir->destination_details->subdistrict_name,
                'city_id' => $this->rajaongkir->destination_details->city_id,
                'city_name' => $this->rajaongkir->destination_details->city,
                'province_id' => $this->rajaongkir->destination_details->province_id,
                'province_name' => $this->rajaongkir->destination_details->province,
            ],
            'couriers' => array_map(function ($courier) {
                return [
                    'courier_code' => data_get($courier, 'code'),
                    'courier_name' => data_get($courier, 'name'),
                    'delivery_discount' => array_merge($this->delivery_discount->toArray(), [
                        'discount_amount' => $this->delivery_discount->discount_amount
                    ]),
                    'list_ongkir' => array_map(function($ongkir) {
                        return [
                            'service_name' => data_get($ongkir, 'service'),
                            'description' => data_get($ongkir, 'description'),
                            'ongkir' => array_map(function ($ongkir_detail) {
                                return [
                                    'harga' => data_get($ongkir_detail, 'value'),
                                    'estimate_day' => data_get($ongkir_detail, 'etd'),
                                    'note' => data_get($ongkir_detail, 'note'),
                                ];
                            }, $ongkir->cost)
                        ];
                    }, $courier->costs)
                ];
            }, $this->rajaongkir->results),
        ];
    }
}

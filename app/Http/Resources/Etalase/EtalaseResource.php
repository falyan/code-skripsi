<?php

namespace App\Http\Resources\Etalase;

use App\Models\Merchant;
use Illuminate\Http\Resources\Json\JsonResource;

class EtalaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $merchant = Merchant::where('id', $this->merchant_id)->first(['id', 'name', 'status']);
        
        return [
            'id' => $this->id,
            'merchant' => $merchant,
            'name' => $this->name,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at
        ];
    }
}

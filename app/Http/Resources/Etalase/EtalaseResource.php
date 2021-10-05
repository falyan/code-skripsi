<?php

namespace App\Http\Resources\Etalase;

use App\Models\Merchant;
use Carbon\Carbon;
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
            'created_at' => $this->formatTime($this->created_at),
            'updated_at' => $this->formatTime($this->updated_at)
        ];
    }

    private function formatTime($time)
    {
        $timstamptz = Carbon::createFromFormat('Y-m-d H:i:s', $time)->setTimezone('Asia/Jakarta')->timestamp;
        return date('Y-m-d H:i:s', $timstamptz);
    }
}

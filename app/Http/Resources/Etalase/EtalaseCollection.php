<?php

namespace App\Http\Resources\Etalase;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class EtalaseCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'etalase' => EtalaseResource::collection($this->collection),
            'links' => [
                "first" => $this->url(1),
                "last" => $this->url($this->lastPage()),
                "prev" => $this->previousPageUrl(),
                "next" => $this->nextPageUrl(),
            ],
            'meta' => [
                "current_page" => $this->currentPage(),
                "from" =>  $this->firstItem(),
                "last_page" => $this->lastPage(),
                "path" =>  $this->path(),
                "per_page" =>  $this->perPage(),
                "to" => $this->lastItem(),
                "total" => $this->total(),
            ],
        ];
    }

    public function toResponse($request)
    {
        return JsonResource::toResponse($request);
    }
}

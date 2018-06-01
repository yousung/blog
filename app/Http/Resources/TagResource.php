<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TagResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        return [
            'text' => $this->name,
            'weight' => $this->posts_count,
            'link' => route('post.index').'?tag='.$this->name,
        ];
    }
}

<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class MediaFileResource extends JsonResource
{
    public function toArray($request)
    {
        // Group versions by label
        $versions = [];
        $thumbnail = null;

        foreach ($this->versions as $version) {
            $data = [
                'url' => $version->url,
                'size' => $version->size,
                'size_type' => $version->size_type,
                'type' => $version->type,
                'dimensions' => $version->dimensions,
            ];

            $versions[$version->label] = $data;

            // Also store thumbnail separately if found
            if ($version->label === 'thumbnail') {
                $thumbnail = $data;
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'original_url' => $this->original_url,
            'thumbnail' => $thumbnail, // 👈 directly added here
            'versions' => $versions,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

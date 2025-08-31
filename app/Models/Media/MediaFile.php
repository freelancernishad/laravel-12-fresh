<?php

namespace App\Models\Media;

use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    protected $fillable = ['name', 'original_url'];

    public function versions()
    {
        return $this->hasMany(MediaFileVersion::class);
    }
}

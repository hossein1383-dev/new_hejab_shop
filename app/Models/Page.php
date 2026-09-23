<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'status', 'meta_title', 'meta_description'];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RssFeed extends Model
{
    protected $fillable = [
        'name', 'url', 'auto_include',
    ];

    protected $casts = [
        'auto_include' => 'boolean',
    ];

    public function articles(): HasMany
    {
        return $this->hasMany(RssArticle::class);
    }
}

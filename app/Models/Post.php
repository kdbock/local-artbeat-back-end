<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'title', 'slug', 'content', 'excerpt', 'author', 'category', 'tags', 'featured_image', 'published_at'
    ];
}

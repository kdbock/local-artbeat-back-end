<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'city', 'type', 'is_free', 'date', 'time', 'guide', 'map_url', 'featured_image', 'end_date', 'capacity', 'price'
    ];
}

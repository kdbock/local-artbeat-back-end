<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TourRegistration extends Model
{
    protected $fillable = [
        'tour_id', 'name', 'email', 'phone', 'notes', 'join_newsletter'
    ];
}

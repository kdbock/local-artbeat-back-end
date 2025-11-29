<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Donation extends Model
{
    protected $fillable = [
        'amount', 'donor_name', 'donor_email', 'artist_honoree', 'stripe_payment_intent', 'status'
    ];
}

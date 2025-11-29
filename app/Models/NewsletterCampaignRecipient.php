<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsletterCampaignRecipient extends Model
{
    protected $fillable = [
        'campaign_id',
        'subscriber_id',
        'status',
        'opened',
        'opened_at',
        'click_count',
        'clicked_at',
        'bounce_type',
        'error_message',
    ];

    protected $casts = [
        'opened' => 'boolean',
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'click_count' => 'integer',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(NewsletterCampaign::class, 'campaign_id');
    }

    public function subscriber(): BelongsTo
    {
        return $this->belongsTo(NewsletterSubscriber::class, 'subscriber_id');
    }
}

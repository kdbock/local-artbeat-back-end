<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsletterSubscriber extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'email', 'name', 'interests', 'confirmed', 'confirmation_token',
        'source', 'confirmed_at', 'status', 'custom_fields',
        'engagement_score', 'last_engaged_at', 'bounce_count', 'bounce_type'
    ];

    protected $casts = [
        'confirmed' => 'boolean',
        'custom_fields' => 'json',
        'confirmed_at' => 'datetime',
        'last_engaged_at' => 'datetime',
    ];

    public function tags()
    {
        return $this->belongsToMany(NewsletterTag::class, 'newsletter_subscriber_tag')->withTimestamps();
    }

    public function segments()
    {
        return $this->belongsToMany(NewsletterSegment::class, 'newsletter_segment_subscriber')->withTimestamps();
    }

    public function campaignRecipients()
    {
        return $this->hasMany(NewsletterCampaignRecipient::class);
    }

    public function confirmEmail()
    {
        $this->update([
            'confirmed' => true,
            'confirmed_at' => now(),
            'status' => 'subscribed',
            'confirmation_token' => null,
        ]);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('confirmed', true)->where('status', 'subscribed');
    }

    public function scopeUnconfirmed($query)
    {
        return $query->where('confirmed', false);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'subscribed');
    }

    public function scopeInactive($query)
    {
        return $query->whereIn('status', ['unsubscribed', 'bounced', 'invalid']);
    }

    public function scopeHighEngagement($query)
    {
        return $query->where('engagement_score', '>=', 75);
    }

    public function scopeLowEngagement($query)
    {
        return $query->where('engagement_score', '<', 25);
    }

    public function scopeWithTag($query, $tagSlug)
    {
        return $query->whereHas('tags', function ($q) use ($tagSlug) {
            $q->where('slug', $tagSlug);
        });
    }

    public function scopeWithSegment($query, $segmentSlug)
    {
        return $query->whereHas('segments', function ($q) use ($segmentSlug) {
            $q->where('slug', $segmentSlug);
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterSegment extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'criteria', 'is_dynamic'];

    protected $casts = [
        'criteria' => 'json',
        'is_dynamic' => 'boolean',
        'last_calculated_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->slug) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function subscribers()
    {
        return $this->belongsToMany(NewsletterSubscriber::class, 'newsletter_segment_subscriber')->withTimestamps();
    }

    public function updateSubscriberCount()
    {
        $this->update(['subscriber_count' => $this->subscribers()->count()]);
    }

    public function recalculateIfDynamic()
    {
        if (!$this->is_dynamic) {
            return;
        }

        $query = NewsletterSubscriber::query();
        $criteria = $this->criteria ?? [];

        if (isset($criteria['engagement']) && $criteria['engagement'] === 'high') {
            $query->highEngagement();
        } elseif (isset($criteria['engagement']) && $criteria['engagement'] === 'low') {
            $query->lowEngagement();
        }

        if (isset($criteria['status']) && is_array($criteria['status'])) {
            $query->whereIn('status', $criteria['status']);
        }

        if (isset($criteria['tags']) && is_array($criteria['tags'])) {
            foreach ($criteria['tags'] as $tagSlug) {
                $query->withTag($tagSlug);
            }
        }

        if (isset($criteria['confirmed_only']) && $criteria['confirmed_only']) {
            $query->confirmed();
        }

        $subscriberIds = $query->pluck('id')->toArray();
        $this->subscribers()->sync($subscriberIds);
        $this->update([
            'subscriber_count' => count($subscriberIds),
            'last_calculated_at' => now(),
        ]);
    }
}

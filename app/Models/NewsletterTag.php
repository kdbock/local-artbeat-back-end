<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsletterTag extends Model
{
    protected $fillable = ['name', 'slug', 'description'];

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
        return $this->belongsToMany(NewsletterSubscriber::class, 'newsletter_subscriber_tag')->withTimestamps();
    }

    public function updateSubscriberCount()
    {
        $this->update(['subscriber_count' => $this->subscribers()->count()]);
    }
}

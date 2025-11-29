<?php

namespace App\Models;

use Illuminate\Support\Str;
use function e;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsletterCampaign extends Model
{
    /**
     * Append latest RSS articles from feeds with auto_include enabled to the campaign content_html.
     * Optionally limit number of articles per feed.
     */
    public function appendAutoIncludedRssArticles(int $articlesPerFeed = 3): void
    {
        $feeds = \App\Models\RssFeed::where('auto_include', true)->get();
        $rssHtml = '';
        foreach ($feeds as $feed) {
            $articles = $feed->articles()->orderByDesc('published_at')->limit($articlesPerFeed)->get();
            if ($articles->isEmpty()) continue;
            $rssHtml .= "<div class='rss-feed-block' style='margin:24px 0;'>\n";
            $rssHtml .= "<div style='font-weight:bold;font-size:1.1em;margin-bottom:8px;'>" . e($feed->name) . "</div>\n";
            foreach ($articles as $article) {
                $rssHtml .= "<div class='rss-article-block' style='border:1px solid #e5e7eb;padding:12px;margin:12px 0;border-radius:8px;'>\n";
                $rssHtml .= "<div style='font-weight:bold;font-size:1em;'>" . e($article->title) . "</div>\n";
                $rssHtml .= "<div style='color:#666;font-size:0.9em;'>" . ($article->published_at ? $article->published_at->format('Y-m-d H:i') : '') . "</div>\n";
                $rssHtml .= "<div style='margin:8px 0;'>" . e(Str::limit(strip_tags($article->summary), 200)) . "</div>\n";
                $rssHtml .= "<a href='" . e($article->url) . "' target='_blank' rel='noopener noreferrer' style='color:#2563eb;text-decoration:underline;'>Read more</a>\n";
                $rssHtml .= "</div>\n";
            }
            $rssHtml .= "</div>\n";
        }
        if ($rssHtml) {
            $this->content_html .= $rssHtml;
            $this->save();
        }
    }
    protected $fillable = [
        'title',
        'subject_line',
        'from_name',
        'from_email',
        'reply_to_email',
        'content_html',
        'content_text',
        'status',
        'scheduled_at',
        'sent_at',
        'recurrence',
        'total_recipients',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'bounce_count',
        'unsubscribe_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'total_recipients' => 'integer',
        'delivered_count' => 'integer',
        'opened_count' => 'integer',
        'clicked_count' => 'integer',
        'bounce_count' => 'integer',
        'unsubscribe_count' => 'integer',
        'recurrence' => 'string',
    ];

    public function recipients(): HasMany
    {
        return $this->hasMany(NewsletterCampaignRecipient::class, 'campaign_id');
    }

    public function getOpenRateAttribute(): float
    {
        if ($this->total_recipients == 0) return 0;
        return ($this->opened_count / $this->total_recipients) * 100;
    }

    public function getClickRateAttribute(): float
    {
        if ($this->total_recipients == 0) return 0;
        return ($this->clicked_count / $this->total_recipients) * 100;
    }

    public function getBounceRateAttribute(): float
    {
        if ($this->total_recipients == 0) return 0;
        return ($this->bounce_count / $this->total_recipients) * 100;
    }

    public function getUnsubscribeRateAttribute(): float
    {
        if ($this->total_recipients == 0) return 0;
        return ($this->unsubscribe_count / $this->total_recipients) * 100;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RssFeed;
use App\Models\RssArticle;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FetchRssFeeds extends Command
{
    protected $signature = 'rss:fetch';
    protected $description = 'Fetch and store articles from all configured RSS feeds';

    public function handle()
    {
        $feeds = RssFeed::all();
        foreach ($feeds as $feed) {
            try {
                $response = Http::timeout(10)->get($feed->url);
                if (!$response->ok()) {
                    Log::warning("Failed to fetch RSS feed: {$feed->url}");
                    continue;
                }
                $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA);
                if (!$xml) {
                    Log::warning("Invalid RSS XML for feed: {$feed->url}");
                    continue;
                }
                $items = $xml->channel->item ?? $xml->entry ?? [];
                foreach ($items as $item) {
                    $guid = (string)($item->guid ?? $item->id ?? $item->link ?? Str::random(32));
                    if (RssArticle::where('feed_id', $feed->id)->where('guid', $guid)->exists()) {
                        continue;
                    }
                    $title = (string)($item->title ?? 'Untitled');
                    $link = (string)($item->link ?? '');
                    if (is_object($item->link) && isset($item->link['href'])) {
                        $link = (string)$item->link['href'];
                    }
                    $summary = (string)($item->description ?? $item->summary ?? '');
                    $published = (string)($item->pubDate ?? $item->published ?? null);
                    $published_at = $published ? date('Y-m-d H:i:s', strtotime($published)) : now();
                    RssArticle::create([
                        'feed_id' => $feed->id,
                        'guid' => $guid,
                        'title' => $title,
                        'url' => $link,
                        'summary' => $summary,
                        'published_at' => $published_at,
                    ]);
                }
            } catch (\Throwable $e) {
                Log::error("Error fetching RSS feed {$feed->url}: " . $e->getMessage());
            }
        }
        $this->info('RSS feeds fetched and articles stored.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\RssFeed;
use App\Models\RssArticle;
use Illuminate\Http\Request;

class RssFeedController extends Controller
{
    public function index()
    {
        return response()->json(RssFeed::withCount('articles')->get());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|url|unique:rss_feeds,url',
            'auto_include' => 'boolean',
        ]);
        $feed = RssFeed::create($data);
        return response()->json($feed, 201);
    }

    public function update(Request $request, $id)
    {
        $feed = RssFeed::findOrFail($id);
        $data = $request->validate([
            'name' => 'sometimes|string|max:255',
            'url' => 'sometimes|url|unique:rss_feeds,url,' . $id,
            'auto_include' => 'boolean',
        ]);
        $feed->update($data);
        return response()->json($feed);
    }

    public function destroy($id)
    {
        $feed = RssFeed::findOrFail($id);
        $feed->delete();
        return response()->json(['deleted' => true]);
    }

    public function articles($id)
    {
        $feed = RssFeed::findOrFail($id);
        return response()->json($feed->articles()->orderByDesc('published_at')->get());
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\NewsletterTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NewsletterTagController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tags = NewsletterTag::withCount('subscribers')->get();
        return response()->json(['tags' => $tags]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:newsletter_tags,name',
            'description' => 'nullable|string',
        ]);

        $tag = NewsletterTag::create($validated);
        return response()->json($tag, 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tag = NewsletterTag::with('subscribers')->findOrFail($id);
        return response()->json($tag);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tag = NewsletterTag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:newsletter_tags,name,' . $id,
            'description' => 'sometimes|nullable|string',
        ]);

        $tag->update($validated);
        return response()->json($tag);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tag = NewsletterTag::findOrFail($id);
        $tag->delete();
        return response()->json(['deleted' => true]);
    }

    public function addToSubscribers(Request $request, $tagId)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tag = NewsletterTag::findOrFail($tagId);
        $validated = $request->validate([
            'subscriber_ids' => 'required|array',
            'subscriber_ids.*' => 'integer|exists:newsletter_subscribers,id',
        ]);

        $tag->subscribers()->syncWithoutDetaching($validated['subscriber_ids']);
        $tag->updateSubscriberCount();

        return response()->json(['success' => true, 'tag' => $tag]);
    }

    public function removeFromSubscribers(Request $request, $tagId)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $tag = NewsletterTag::findOrFail($tagId);
        $validated = $request->validate([
            'subscriber_ids' => 'required|array',
            'subscriber_ids.*' => 'integer|exists:newsletter_subscribers,id',
        ]);

        $tag->subscribers()->detach($validated['subscriber_ids']);
        $tag->updateSubscriberCount();

        return response()->json(['success' => true, 'tag' => $tag]);
    }
}

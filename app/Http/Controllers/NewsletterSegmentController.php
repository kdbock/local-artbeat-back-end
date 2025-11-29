<?php

namespace App\Http\Controllers;

use App\Models\NewsletterSegment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NewsletterSegmentController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $segments = NewsletterSegment::withCount('subscribers')->get();
        return response()->json(['segments' => $segments]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|unique:newsletter_segments,name',
            'description' => 'nullable|string',
            'criteria' => 'nullable|json',
            'is_dynamic' => 'sometimes|boolean',
        ]);

        $validated['is_dynamic'] = $validated['is_dynamic'] ?? true;
        $segment = NewsletterSegment::create($validated);

        if ($segment->is_dynamic) {
            $segment->recalculateIfDynamic();
        }

        return response()->json($segment, 201);
    }

    public function show($id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $segment = NewsletterSegment::with('subscribers')->findOrFail($id);
        return response()->json($segment);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $segment = NewsletterSegment::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:newsletter_segments,name,' . $id,
            'description' => 'sometimes|nullable|string',
            'criteria' => 'sometimes|nullable|json',
            'is_dynamic' => 'sometimes|boolean',
        ]);

        $segment->update($validated);

        if ($segment->is_dynamic) {
            $segment->recalculateIfDynamic();
        }

        return response()->json($segment);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $segment = NewsletterSegment::findOrFail($id);
        $segment->delete();
        return response()->json(['deleted' => true]);
    }

    public function recalculate($id)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $segment = NewsletterSegment::findOrFail($id);

        if (!$segment->is_dynamic) {
            return response()->json(['error' => 'Cannot recalculate static segment'], 400);
        }

        $segment->recalculateIfDynamic();
        return response()->json(['success' => true, 'segment' => $segment]);
    }

    public function addSubscribers(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $segment = NewsletterSegment::findOrFail($id);

        $validated = $request->validate([
            'subscriber_ids' => 'required|array',
            'subscriber_ids.*' => 'integer|exists:newsletter_subscribers,id',
        ]);

        $segment->subscribers()->syncWithoutDetaching($validated['subscriber_ids']);
        $segment->updateSubscriberCount();

        return response()->json(['success' => true, 'segment' => $segment]);
    }

    public function removeSubscribers(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $segment = NewsletterSegment::findOrFail($id);

        $validated = $request->validate([
            'subscriber_ids' => 'required|array',
            'subscriber_ids.*' => 'integer|exists:newsletter_subscribers,id',
        ]);

        $segment->subscribers()->detach($validated['subscriber_ids']);
        $segment->updateSubscriberCount();

        return response()->json(['success' => true, 'segment' => $segment]);
    }
}

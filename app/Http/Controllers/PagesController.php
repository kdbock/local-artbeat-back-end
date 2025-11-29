<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PagesController extends Controller
{
    public function landing()
    {
        // In production, pull from DB or config
        return response()->json([
            'hero' => [
                'tagline' => 'Discover Local Art. Empower Artists.',
                'app_links' => [
                    'ios' => 'https://apps.apple.com/us/app/local-artbeat/id6746663501',
                    'android' => 'https://play.google.com/'
                ],
            ],
            'features' => [
                'Art discovery', 'Artist tools', 'Art walks', 'Community feed', 'Local business partnerships'
            ],
            'snapshot' => [],
            'tours_preview' => [],
            'why_artists' => [],
            'cta' => [
                'download' => true,
                'newsletter' => true
            ],
            'donation_ribbon' => true
        ]);
    }

    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $pages = \App\Models\Page::all();
        return response()->json($pages);
    }

    public function show($id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $page = \App\Models\Page::findOrFail($id);
        return response()->json(['page' => $page]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:pages,slug',
            'content' => 'required',
            'status' => 'nullable|string',
        ]);
        $page = \App\Models\Page::create($validated);
        return response()->json(['page' => $page], 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $page = \App\Models\Page::findOrFail($id);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:pages,slug,' . $page->id,
            'content' => 'sometimes|required',
            'status' => 'nullable|string',
        ]);
        $page->update($validated);
        return response()->json(['page' => $page]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $page = \App\Models\Page::findOrFail($id);
        $page->delete();
        return response()->json(['deleted' => true]);
    }
}

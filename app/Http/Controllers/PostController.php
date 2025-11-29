<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{

    public function index()
    {
        $posts = \App\Models\Post::orderByDesc('published_at')->get();
        $posts = $posts->map(function ($post) {
            return [
                'id' => $post->id,
                'title' => $post->title,
                'slug' => $post->slug,
                'excerpt' => $post->excerpt,
                'content' => $post->content,
                'featured_image_url' => $post->featured_image,
                'published_at' => $post->published_at,
                'created_at' => $post->created_at,
                'updated_at' => $post->updated_at,
            ];
        });
        return response()->json($posts);
    }

    public function show($slug)
    {
        $post = \App\Models\Post::where('slug', $slug)->first();
        if (!$post) {
            return response()->json(['error' => 'Post not found'], 404);
        }
        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'featured_image_url' => $post->featured_image,
            'published_at' => $post->published_at,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor() || $user->isAuthor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:posts,slug',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'author' => 'required|string',
            'category' => 'nullable|string',
            'tags' => 'nullable|string',
            'featured_image' => 'nullable|string',
            'published_at' => 'nullable|date',
        ]);
        $post = \App\Models\Post::create($validated);
        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'featured_image_url' => $post->featured_image,
            'published_at' => $post->published_at,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor() || $user->isAuthor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $post = \App\Models\Post::findOrFail($id);
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:posts,slug,' . $post->id,
            'content' => 'sometimes|required|string',
            'excerpt' => 'nullable|string',
            'author' => 'sometimes|required|string',
            'category' => 'nullable|string',
            'tags' => 'nullable|string',
            'featured_image' => 'nullable|string',
            'published_at' => 'nullable|date',
        ]);
        $post->update($validated);
        return response()->json([
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'excerpt' => $post->excerpt,
            'content' => $post->content,
            'featured_image_url' => $post->featured_image,
            'published_at' => $post->published_at,
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $post = \App\Models\Post::findOrFail($id);
        $post->delete();
        return response()->json(['deleted' => true]);
    }
}

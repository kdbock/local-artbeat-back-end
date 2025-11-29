<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TourRegistration;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class TourController extends Controller
{
    public function index()
    {
        $tours = \App\Models\Tour::orderByDesc('date')->get();
        $tours = $tours->map(function ($tour) {
            return [
                'id' => $tour->id,
                'title' => $tour->name,
                'slug' => $tour->slug,
                'description' => $tour->description,
                'location' => $tour->city,
                'start_date' => $tour->date,
                'end_date' => $tour->end_date ?? null,
                'featured_image_url' => $tour->featured_image,
                'capacity' => $tour->capacity ?? null,
                'price' => $tour->price ?? null,
                'created_at' => $tour->created_at,
                'updated_at' => $tour->updated_at,
            ];
        });
        return response()->json($tours);
    }

    public function show($slug)
    {
        $tour = \App\Models\Tour::where('slug', $slug)->first();
        if (!$tour) {
            return response()->json(['error' => 'Tour not found'], 404);
        }
        return response()->json([
            'id' => $tour->id,
            'title' => $tour->name,
            'slug' => $tour->slug,
            'description' => $tour->description,
            'location' => $tour->city,
            'start_date' => $tour->date,
            'end_date' => $tour->end_date ?? null,
            'featured_image_url' => $tour->featured_image,
            'capacity' => $tour->capacity ?? null,
            'price' => $tour->price ?? null,
            'created_at' => $tour->created_at,
            'updated_at' => $tour->updated_at,
        ]);
    }

    public function register(Request $request, $slug)
    {
        $tour = \App\Models\Tour::where('slug', $slug)->first();
        if (!$tour) {
            return response()->json(['error' => 'Tour not found'], 404);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:32',
            'notes' => 'nullable|string',
            'join_newsletter' => 'boolean',
        ]);
        $registration = TourRegistration::create([
            'tour_id' => $tour->id,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'join_newsletter' => $validated['join_newsletter'] ?? false,
        ]);
        // Optionally add to newsletter
        if (!empty($validated['join_newsletter'])) {
            \App\Models\NewsletterSubscriber::firstOrCreate([
                'email' => $validated['email']
            ], [
                'name' => $validated['name'],
                'confirmed' => false,
                'confirmation_token' => bin2hex(random_bytes(16)),
            ]);
        }
        // Send confirmation email
        Mail::to($registration->email)->send(new \App\Mail\TourRegistrationConfirmation($registration, $tour));
        return response()->json(['registered' => true, 'tour' => $slug]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor() || $user->isAuthor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:tours,slug',
            'description' => 'required|string',
            'city' => 'nullable|string',
            'type' => 'nullable|string',
            'is_free' => 'boolean',
            'date' => 'required|date',
            'time' => 'nullable|string',
            'guide' => 'nullable|string',
            'map_url' => 'nullable|string',
            'featured_image' => 'nullable|string',
        ]);
        $tour = \App\Models\Tour::create($validated);
        return response()->json([
            'id' => $tour->id,
            'title' => $tour->name,
            'slug' => $tour->slug,
            'description' => $tour->description,
            'location' => $tour->city,
            'start_date' => $tour->date,
            'end_date' => $tour->end_date ?? null,
            'featured_image_url' => $tour->featured_image,
            'capacity' => $tour->capacity ?? null,
            'price' => $tour->price ?? null,
            'created_at' => $tour->created_at,
            'updated_at' => $tour->updated_at,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor() || $user->isAuthor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $tour = \App\Models\Tour::findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:tours,slug,' . $tour->id,
            'description' => 'sometimes|required|string',
            'city' => 'nullable|string',
            'type' => 'nullable|string',
            'is_free' => 'boolean',
            'date' => 'sometimes|required|date',
            'time' => 'nullable|string',
            'guide' => 'nullable|string',
            'map_url' => 'nullable|string',
            'featured_image' => 'nullable|string',
        ]);
        $tour->update($validated);
        return response()->json([
            'id' => $tour->id,
            'title' => $tour->name,
            'slug' => $tour->slug,
            'description' => $tour->description,
            'location' => $tour->city,
            'start_date' => $tour->date,
            'end_date' => $tour->end_date ?? null,
            'featured_image_url' => $tour->featured_image,
            'capacity' => $tour->capacity ?? null,
            'price' => $tour->price ?? null,
            'created_at' => $tour->created_at,
            'updated_at' => $tour->updated_at,
        ]);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $tour = \App\Models\Tour::findOrFail($id);
        $tour->delete();
        return response()->json(['deleted' => true]);
    }
}

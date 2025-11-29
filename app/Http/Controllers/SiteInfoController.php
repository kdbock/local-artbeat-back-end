<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SiteInfoController extends Controller
{
    public function index()
    {
        return response()->json([
            'name' => 'Local ARTbeat',
            'mission' => 'Democratize art discovery and creation while empowering artists with professional tools and community connections.',
            'contact_email' => 'info@localartbeat.com',
            'social' => [
                'instagram' => 'https://instagram.com/localartbeat',
                'twitter' => 'https://twitter.com/localartbeat'
            ]
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        // This is a stub. In a real app, you would update a SiteSettings model/table.
        // For now, just return the posted data as if it was saved.
        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'mission' => 'sometimes|required|string',
            'contact_email' => 'sometimes|required|email',
            'social' => 'sometimes|array',
        ]);
        return response()->json(['site_info' => $validated]);
    }
}

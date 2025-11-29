<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

class DonationController extends Controller
{
    public function createIntent(Request $request)
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $amount = (int) ($request->input('amount') * 100); // Stripe expects cents
        $currency = 'usd'; // Or make configurable
        $intent = PaymentIntent::create([
            'amount' => $amount,
            'currency' => $currency,
            'metadata' => [
                'donor_name' => $request->input('donor_name'),
                'donor_email' => $request->input('donor_email'),
                'artist_honoree' => $request->input('artist_honoree'),
            ],
            'receipt_email' => $request->input('donor_email'),
        ]);
        $donation = \App\Models\Donation::create([
            'amount' => $request->input('amount'),
            'donor_name' => $request->input('donor_name'),
            'donor_email' => $request->input('donor_email'),
            'artist_honoree' => $request->input('artist_honoree'),
            'stripe_payment_intent' => $intent->id,
            'status' => 'pending',
        ]);
        return response()->json(['clientSecret' => $intent->client_secret, 'donation_id' => $donation->id]);
    }

    public function confirm(Request $request)
    {
        $donation = \App\Models\Donation::find($request->input('donation_id'));
        if (!$donation) {
            return response()->json(['error' => 'Donation not found'], 404);
        }
        $donation->status = 'confirmed';
        $donation->save();
        // Send receipt email
        Mail::to($donation->donor_email)->send(new \App\Mail\DonationReceiptMail($donation));
        return response()->json(['success' => true]);
    }

    public function index()
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $donations = \App\Models\Donation::orderByDesc('created_at')->get();
        return response()->json(['donations' => $donations]);
    }
}

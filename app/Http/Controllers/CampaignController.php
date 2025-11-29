<?php

namespace App\Http\Controllers;

use App\Models\NewsletterCampaign;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterSubscriber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewsletterCampaignMail;

class CampaignController extends Controller
{
    private function authorizeUser()
    {
        $user = Auth::user();
        if (!$user || !($user->isAdmin() || $user->isEditor())) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        return true;
    }

    public function index()
    {
        $auth = $this->authorizeUser();
        if ($auth !== true) return $auth;

        $campaigns = NewsletterCampaign::orderBy('created_at', 'desc')->get();
        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $auth = $this->authorizeUser();
        if ($auth !== true) return $auth;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subject_line' => 'required|string|max:255',
            'from_name' => 'required|string|max:255',
            'from_email' => 'required|email',
            'reply_to_email' => 'required|email',
            'content_html' => 'required|string',
            'content_text' => 'nullable|string',
            'status' => 'in:draft,scheduled',
            'scheduled_at' => 'nullable|date_format:Y-m-d H:i:s|after:now',
            'recurrence' => 'nullable|in:none,daily,weekly,monthly',
        ]);

        $campaign = NewsletterCampaign::create($validated);
        // Auto-append RSS articles if any feeds have auto_include enabled
        $this->maybeAppendRss($campaign);
        return response()->json($campaign, 201);
    }

    public function show($id)
    {
        $auth = $this->authorizeUser();
        if ($auth !== true) return $auth;

        $campaign = NewsletterCampaign::findOrFail($id);
        return response()->json($campaign);
    }

    public function update(Request $request, $id)
    {
        $auth = $this->authorizeUser();
        if ($auth !== true) return $auth;

        $campaign = NewsletterCampaign::findOrFail($id);

        if ($campaign->status !== 'draft') {
            return response()->json(['error' => 'Only draft campaigns can be edited'], 400);
        }

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'subject_line' => 'sometimes|string|max:255',
            'from_name' => 'sometimes|string|max:255',
            'from_email' => 'sometimes|email',
            'reply_to_email' => 'sometimes|email',
            'content_html' => 'sometimes|string',
            'content_text' => 'sometimes|nullable|string',
            'status' => 'sometimes|in:draft,scheduled',
            'scheduled_at' => 'sometimes|nullable|date_format:Y-m-d H:i:s',
            'recurrence' => 'nullable|in:none,daily,weekly,monthly',
        ]);


        $campaign->update($validated);
        // Auto-append RSS articles if any feeds have auto_include enabled
        $this->maybeAppendRss($campaign);
        return response()->json($campaign);
    }

    public function destroy($id)
    {
        $auth = $this->authorizeUser();
        if ($auth !== true) return $auth;

        $campaign = NewsletterCampaign::findOrFail($id);

        if ($campaign->status === 'sent') {
            return response()->json(['error' => 'Cannot delete sent campaigns'], 400);
        }

        $campaign->delete();
        return response()->json(['deleted' => true]);
    }

    public function sendTest(Request $request, $id)
    {
        $auth = $this->authorizeUser();
        if ($auth !== true) return $auth;

        $validated = $request->validate([
            'test_emails' => 'required|array|min:1',
            'test_emails.*' => 'email',
        ]);

        $campaign = NewsletterCampaign::findOrFail($id);

        foreach ($validated['test_emails'] as $email) {
            try {
                Mail::to($email)->send(new NewsletterCampaignMail($campaign));
            } catch (\Exception $e) {
                return response()->json(['error' => 'Failed to send test email: ' . $e->getMessage()], 500);
            }
        }

        return response()->json(['message' => 'Test emails sent successfully']);
    }

    public function send($id)
    {
        $auth = $this->authorizeUser();
        if ($auth !== true) return $auth;

        $campaign = NewsletterCampaign::findOrFail($id);

        if ($campaign->status === 'sent') {
            return response()->json(['error' => 'Campaign already sent'], 400);
        }

        if ($campaign->status === 'canceled') {
            return response()->json(['error' => 'Campaign is canceled'], 400);
        }

        $campaign->update(['status' => 'sending']);

        $subscribers = NewsletterSubscriber::where('confirmed', true)->get();

        if ($subscribers->isEmpty()) {
            $campaign->update(['status' => 'draft']);
            return response()->json(['error' => 'No confirmed subscribers to send to'], 400);
        }

        $campaign->update(['total_recipients' => $subscribers->count()]);

        foreach ($subscribers as $subscriber) {
            try {
                NewsletterCampaignRecipient::firstOrCreate(
                    [
                        'campaign_id' => $campaign->id,
                        'subscriber_id' => $subscriber->id,
                    ],
                    ['status' => 'pending']
                );

                Mail::to($subscriber->email)->send(new NewsletterCampaignMail($campaign));

                NewsletterCampaignRecipient::where('campaign_id', $campaign->id)
                    ->where('subscriber_id', $subscriber->id)
                    ->update(['status' => 'sent']);

                $campaign->increment('delivered_count');
            } catch (\Exception $e) {
                NewsletterCampaignRecipient::where('campaign_id', $campaign->id)
                    ->where('subscriber_id', $subscriber->id)
                    ->update([
                        'status' => 'failed',
                        'error_message' => $e->getMessage()
                    ]);
            }
        }

        $campaign->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return response()->json([
            'message' => 'Campaign sent successfully',
            'campaign' => $campaign
        ]);
    }

    public function cancel($id)
    {
        $auth = $this->authorizeUser();
        if ($auth !== true) return $auth;

        $campaign = NewsletterCampaign::findOrFail($id);

        if ($campaign->status === 'sent') {
            return response()->json(['error' => 'Cannot cancel sent campaigns'], 400);
        }

        $campaign->update(['status' => 'canceled']);
        return response()->json(['message' => 'Campaign canceled']);
    }

    public function getAnalytics($id)
    {
        $auth = $this->authorizeUser();
        if ($auth !== true) return $auth;

        $campaign = NewsletterCampaign::findOrFail($id);

        $recipients = NewsletterCampaignRecipient::where('campaign_id', $id)->get();

        return response()->json([
            'campaign' => $campaign,
            'analytics' => [
                'total_recipients' => $campaign->total_recipients,
                'delivered' => $campaign->delivered_count,
                'opened' => $campaign->opened_count,
                'clicked' => $campaign->clicked_count,
                'bounced' => $campaign->bounce_count,
                'unsubscribed' => $campaign->unsubscribe_count,
                'open_rate' => $campaign->open_rate,
                'click_rate' => $campaign->click_rate,
                'bounce_rate' => $campaign->bounce_rate,
                'unsubscribe_rate' => $campaign->unsubscribe_rate,
            ],
            'recipients' => $recipients,
        ]);
    }
    /**
     * Append RSS articles to campaign if any feeds have auto_include enabled.
     */
    private function maybeAppendRss($campaign)
    {
        if (\App\Models\RssFeed::where('auto_include', true)->exists()) {
            $campaign->appendAutoIncludedRssArticles();
        }
    }
}

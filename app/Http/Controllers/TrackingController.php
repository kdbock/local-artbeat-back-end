<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\NewsletterCampaignRecipient;
use App\Models\NewsletterCampaign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;

class TrackingController extends Controller
{
    // Open tracking pixel
    public function open($recipient)
    {
        $recipient = NewsletterCampaignRecipient::find($recipient);
        if ($recipient && !$recipient->opened) {
            $recipient->opened = true;
            $recipient->opened_at = Carbon::now();
            $recipient->save();
            // Increment campaign open count
            if ($recipient->campaign_id) {
                NewsletterCampaign::where('id', $recipient->campaign_id)->increment('opened_count');
            }
        }
        // Return a 1x1 transparent gif
        return response(base64_decode('R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw=='))
            ->header('Content-Type', 'image/gif');
    }

    // Click tracking
    public function click(Request $request, $recipient)
    {
        $recipient = NewsletterCampaignRecipient::find($recipient);
        $url = $request->query('url');
        if ($recipient && $url) {
            $recipient->click_count = ($recipient->click_count ?? 0) + 1;
            $recipient->clicked_at = Carbon::now();
            $recipient->save();
            // Increment campaign click count
            if ($recipient->campaign_id) {
                NewsletterCampaign::where('id', $recipient->campaign_id)->increment('clicked_count');
            }
            return Redirect::away($url);
        }
        return abort(404);
    }

    // Unsubscribe tracking
    public function unsubscribe($recipient)
    {
        $recipient = NewsletterCampaignRecipient::find($recipient);
        if ($recipient) {
            $recipient->status = 'unsubscribed';
            $recipient->save();
            // Increment campaign unsubscribe count
            if ($recipient->campaign_id) {
                NewsletterCampaign::where('id', $recipient->campaign_id)->increment('unsubscribe_count');
            }
            return response('You have been unsubscribed.');
        }
        return abort(404);
    }
}

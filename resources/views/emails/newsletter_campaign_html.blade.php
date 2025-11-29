
@php
    // Find the recipient record for this campaign and subscriber
    $recipient = isset($subscriber) ? \App\Models\NewsletterCampaignRecipient::where('campaign_id', $campaign->id)->where('subscriber_id', $subscriber->id)->first() : null;
    $recipientId = $recipient ? $recipient->id : 'unknown';
    // Helper to rewrite links for click tracking
    function track_links($html, $recipientId) {
        return preg_replace_callback('/<a\\s+[^>]*href=([\"\'])(.*?)\\1/si', function($matches) use ($recipientId) {
            $url = urlencode($matches[2]);
            $trackUrl = url("/newsletter/click/{$recipientId}?url={$url}");
            return str_replace($matches[2], $trackUrl, $matches[0]);
        }, $html);
    }
@endphp

{!! isset($recipientId) ? track_links($campaign->content_html, $recipientId) : $campaign->content_html !!}

<!-- Open tracking pixel -->
@if($recipientId !== 'unknown')
    <img src="{{ url('/newsletter/open/' . $recipientId) }}" width="1" height="1" style="display:none;" alt="" />
@endif

<hr style="margin-top: 2rem; margin-bottom: 2rem;">
<p style="font-size: 12px; color: #666;">
    @if($recipientId !== 'unknown')
        <a href="{{ url('/newsletter/unsubscribe/' . $recipientId) }}" style="color: #0066cc;">Unsubscribe</a>
    @else
        Unsubscribe
    @endif
</p>

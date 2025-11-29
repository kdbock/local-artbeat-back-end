<?php

namespace App\Mail;

use App\Models\NewsletterCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterCampaignMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterCampaign $campaign)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: $this->campaign->from_email,
            replyTo: $this->campaign->reply_to_email,
            subject: $this->campaign->subject_line,
        );
    }

    public function content(): Content
    {
        return new Content(
            html: 'emails.newsletter_campaign_html',
            text: 'emails.newsletter_campaign_text',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}

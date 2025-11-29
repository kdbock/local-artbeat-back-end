<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TourRegistrationConfirmation extends Mailable
{
    use Queueable, SerializesModels;

    public $registration;
    public $tour;

    public function __construct($registration, $tour)
    {
        $this->registration = $registration;
        $this->tour = $tour;
    }

    public function build()
    {
        return $this->subject('Tour Registration Confirmation')
            ->view('emails.tour_registration_confirmation');
    }
}

<?php

namespace App\Mail;

use App\Filament\Resources\ShippingRequestResource;
use App\Models\ShippingRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingSubmitted extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ShippingRequest $request,
        public bool $forAdmin = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->forAdmin
            ? 'Pemesanan Baru dari ' . $this->request->name . ' - ATL Express'
            : 'Pemesanan Anda Diterima - ATL Express';

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.booking-submitted',
            with: [
                'request' => $this->request,
                'forAdmin' => $this->forAdmin,
                'adminUrl' => $this->forAdmin
                    ? ShippingRequestResource::getUrl('view', ['record' => $this->request])
                    : null,
            ],
        );
    }
}
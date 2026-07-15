<?php

namespace App\Mail;

use App\Models\Client;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class StatementMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Client $client,
        public User $sender,
        public Collection $invoices,
        public Collection $allPayments,
        public ?string $customMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->sender->email, $this->sender->name),
            subject: "Estado de Cuenta — {$this->client->name} — {$this->sender->name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.statement');
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('cartera.statement-print', [
            'client'      => $this->client,
            'invoices'    => $this->invoices,
            'allPayments' => $this->allPayments,
            'user'        => $this->sender,
        ])->setPaper('letter', 'portrait');

        $filename = 'estado-cuenta-' . Str::slug($this->client->name) . '.pdf';

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                $filename
            )->withMime('application/pdf'),
        ];
    }
}

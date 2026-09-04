<?php

namespace App\Mail;

use App\Models\GeneratedDocument;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ProposalDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public GeneratedDocument $document,
        public User $sender,
        public ?string $customMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address($this->sender->email, $this->sender->name),
            subject: "{$this->document->documentType->label} {$this->document->full_number} — {$this->sender->name}",
        );
    }

    public function content(): Content
    {
        // Vista 100% genérica (solo usa $document/$sender/$customMessage) — la
        // reutiliza tal cual ContractDocumentMail, sin duplicar plantilla de correo.
        return new Content(view: 'emails.contract-document');
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('documents.proposals.pdf', ['document' => $this->document])->setPaper('letter');

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                "{$this->document->full_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}

<?php

namespace App\Mail;

use App\Models\Payroll;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PayslipMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payroll $payroll,
        public User $sender,
        public ?string $customMessage = null,
    ) {}

    public function envelope(): Envelope
    {
        $period = $this->payroll->payrollPeriod;

        return new Envelope(
            from: new Address($this->sender->email, $this->sender->name),
            subject: "Desprendible de pago — {$period->number} — {$this->sender->name}",
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payslip');
    }

    public function attachments(): array
    {
        $pdf = Pdf::loadView('payroll.pdf', [
            'payroll' => $this->payroll,
            'user' => $this->sender,
        ])->setPaper('letter', 'portrait');

        $number = $this->payroll->payrollPeriod->number;

        return [
            Attachment::fromData(
                fn () => $pdf->output(),
                "desprendible-{$number}-{$this->payroll->employee->document_number}.pdf"
            )->withMime('application/pdf'),
        ];
    }
}

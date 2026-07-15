<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePaymentRequest;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    public function store(StorePaymentRequest $request, Invoice $invoice)
    {
        abort_if($invoice->user_id !== auth()->id(), 403);

        DB::transaction(function () use ($request, $invoice) {
            $receiptPath = null;
            if ($request->hasFile('receipt')) {
                $receiptPath = $request->file('receipt')->store('receipts', 'public');
            }

            $invoice->payments()->create([
                'user_id'        => auth()->id(),
                'amount'         => $request->amount,
                'payment_date'   => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference'      => $request->reference,
                'notes'          => $request->notes,
                'receipt_path'   => $receiptPath,
            ]);

            $invoice->load('payments');

            if ($invoice->balance <= 0.01) {
                $invoice->update(['status' => 'paid']);
            } elseif ($invoice->status === 'draft') {
                $status = $invoice->due_date && Carbon::parse($invoice->due_date)->isPast()
                    ? 'overdue'
                    : 'sent';
                $invoice->update(['status' => $status]);
            }
        });

        return $this->redirectBack($request, $invoice, 'Pago registrado correctamente.');
    }

    public function destroy(Payment $payment)
    {
        $invoice = $payment->invoice;
        abort_if($invoice->user_id !== auth()->id(), 403);

        DB::transaction(function () use ($payment, $invoice) {
            if ($payment->receipt_path) {
                Storage::disk('public')->delete($payment->receipt_path);
            }

            $payment->delete();

            $invoice->load('payments');

            if ($invoice->status === 'paid' && $invoice->balance > 0.01) {
                $status = $invoice->due_date && Carbon::parse($invoice->due_date)->isPast()
                    ? 'overdue'
                    : 'sent';
                $invoice->update(['status' => $status]);
            }
        });

        return $this->redirectBack(request(), $invoice, 'Pago eliminado.');
    }

    public function receipt(Payment $payment)
    {
        $invoice = $payment->invoice;
        abort_if($invoice->user_id !== auth()->id(), 403);
        abort_if(! $payment->receipt_path, 404);

        $path = storage_path('app/public/' . $payment->receipt_path);
        abort_if(! file_exists($path), 404);

        return response()->file($path);
    }

    private function redirectBack($request, Invoice $invoice, string $message)
    {
        $from = $request->input('_from', $request->headers->get('referer', ''));

        if (str_contains($from, '/cartera/cliente/')) {
            return redirect()
                ->route('cartera.client', $invoice->client_id)
                ->with('success', $message);
        }

        if (str_contains($from, '/invoices/')) {
            return redirect()
                ->route('invoices.show', $invoice)
                ->with('success', $message);
        }

        return redirect()
            ->route('cartera.show', $invoice)
            ->with('success', $message);
    }
}

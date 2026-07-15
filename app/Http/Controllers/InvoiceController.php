<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Mail\InvoiceMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        Invoice::syncOverdueForUser($request->user()->id);

        $query = $request->user()->invoices()->with('client:id,name');

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('number', 'like', "%{$search}%")
                  ->orWhereHas('client', fn($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $invoices = $query->orderByDesc('issue_date')->orderByDesc('id')->paginate(20)->withQueryString();

        $counts = $request->user()->invoices()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('invoices.index', compact('invoices', 'counts'));
    }

    public function create(Request $request): View
    {
        $services = $request->user()->services()->where('status', 'active')->orderBy('name')->get();
        $clients  = $request->user()->clients()->where('status', 'active')->orderBy('name')
                        ->get(['id', 'name', 'document_type', 'document_number', 'dv', 'invoice_prefix', 'invoice_consecutive']);

        return view('invoices.create', compact('services', 'clients'));
    }

    public function store(StoreInvoiceRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $invoice = DB::transaction(function () use ($data, $request) {
            $client = Client::where('id', $data['client_id'])
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->firstOrFail();

            $number = $this->nextNumberForClient($client);

            $invoice = $request->user()->invoices()->create([
                'client_id'          => $data['client_id'],
                'number'             => $number,
                'issue_date'         => $data['issue_date'],
                'due_date'           => $data['due_date'] ?? null,
                'status'             => 'sent',
                'payment_method'     => $data['payment_method'] ?? null,
                'notes'              => $data['notes'] ?? null,
                'subtotal'           => 0,
                'vat_amount'         => 0,
                'discount_amount'    => 0,
                'withholding_rate'   => 0,
                'withholding_amount' => 0,
                'total'              => 0,
            ]);

            [$discountAmount, $subtotal, $vatAmount] = $this->saveItems($invoice, $data['items']);

            $withholdingRate   = (float) ($data['withholding_rate'] ?? 0);
            $withholdingAmount = round($subtotal * $withholdingRate / 100, 2);

            $invoice->update([
                'subtotal'           => $subtotal,
                'vat_amount'         => $vatAmount,
                'discount_amount'    => $discountAmount,
                'withholding_rate'   => $withholdingRate,
                'withholding_amount' => $withholdingAmount,
                'total'              => $subtotal + $vatAmount - $withholdingAmount,
            ]);

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Cuenta de cobro creada correctamente.');
    }

    public function show(Request $request, Invoice $invoice): View
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);

        $invoice->load('client', 'items.service', 'payments');

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Request $request, Invoice $invoice): View
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);
        abort_if(in_array($invoice->status, ['paid', 'cancelled']), 403);

        $services = $request->user()->services()->where('status', 'active')->orderBy('name')->get();
        $clients  = $request->user()->clients()->where('status', 'active')->orderBy('name')
                      ->get(['id', 'name', 'document_type', 'document_number', 'dv', 'invoice_prefix', 'invoice_consecutive']);

        $invoice->load('client', 'items.service');

        return view('invoices.edit', compact('invoice', 'services', 'clients'));
    }

    public function update(UpdateInvoiceRequest $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);
        abort_if(in_array($invoice->status, ['paid', 'cancelled']), 403);

        $data = $request->validated();

        DB::transaction(function () use ($invoice, $data) {
            $invoice->update([
                'client_id'      => $data['client_id'],
                'issue_date'     => $data['issue_date'],
                'due_date'       => $data['due_date'] ?? null,
                'payment_method' => $data['payment_method'] ?? null,
                'notes'          => $data['notes'] ?? null,
                // status is managed automatically — not accepted from form
            ]);

            $invoice->items()->delete();
            [$discountAmount, $subtotal, $vatAmount] = $this->saveItems($invoice, $data['items']);

            $withholdingRate   = (float) ($data['withholding_rate'] ?? 0);
            $withholdingAmount = round($subtotal * $withholdingRate / 100, 2);

            $invoice->update([
                'subtotal'           => $subtotal,
                'vat_amount'         => $vatAmount,
                'discount_amount'    => $discountAmount,
                'withholding_rate'   => $withholdingRate,
                'withholding_amount' => $withholdingAmount,
                'total'              => $subtotal + $vatAmount - $withholdingAmount,
            ]);
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Cuenta de cobro actualizada.');
    }

    public function destroy(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Cuenta de cobro eliminada.');
    }

    public function printView(Request $request, Invoice $invoice): View
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);

        $invoice->load('client', 'items.service');
        $user = $request->user();

        return view('invoices.print', compact('invoice', 'user'));
    }

    public function pdf(Request $request, Invoice $invoice): Response
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);

        $invoice->load('client', 'items.service');
        $user = $request->user();

        $pdf = Pdf::loadView('invoices.pdf', compact('invoice', 'user'))
            ->setPaper('letter', 'portrait');

        return $pdf->download("cuenta-cobro-{$invoice->number}.pdf");
    }

    public function pdfWithStatement(Request $request, Invoice $invoice): Response
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);

        $invoice->load('client', 'items.service', 'payments');
        $user = $request->user();

        [$clientInvoices, $allPayments] = $this->loadClientStatement($request->user()->id, $invoice->client_id);

        $pdf = Pdf::loadView('invoices.pdf-with-statement', compact('invoice', 'user', 'clientInvoices', 'allPayments'))
            ->setPaper('letter', 'portrait');

        return $pdf->download("cuenta-cobro-{$invoice->number}-estado.pdf");
    }

    public function printWithStatement(Request $request, Invoice $invoice): View
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);

        $invoice->load('client', 'items.service', 'payments');
        $user = $request->user();

        [$clientInvoices, $allPayments] = $this->loadClientStatement($request->user()->id, $invoice->client_id);

        return view('invoices.print-with-statement', compact('invoice', 'user', 'clientInvoices', 'allPayments'));
    }

    private function loadClientStatement(int $userId, int $clientId): array
    {
        $clientInvoices = Invoice::where('user_id', $userId)
            ->where('client_id', $clientId)
            ->with('payments')
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $allPayments = $clientInvoices->flatMap(function ($inv) {
            return $inv->payments->map(fn($p) => (object)[
                'payment_date'   => $p->payment_date,
                'invoice_number' => $inv->number,
                'payment_method' => $p->payment_method,
                'reference'      => $p->reference,
                'amount'         => $p->amount,
            ]);
        })->sortBy('payment_date')->values();

        return [$clientInvoices, $allPayments];
    }

    public function sendEmail(Request $request, Invoice $invoice): RedirectResponse
    {
        abort_if($invoice->user_id !== $request->user()->id, 403);

        $data = $request->validate([
            'email'   => 'required|email|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        $invoice->load('client', 'items.service');

        Mail::to($data['email'])->send(
            new InvoiceMail($invoice, $request->user(), $data['message'] ?? null)
        );

        return back()->with('success', "Cuenta {$invoice->number} enviada a {$data['email']}.");
    }

    private function nextNumberForClient(Client $client): string
    {
        $client->increment('invoice_consecutive');
        $client->refresh();

        $consecutive = str_pad($client->invoice_consecutive, 4, '0', STR_PAD_LEFT);
        $prefix      = trim($client->invoice_prefix ?? '');

        return $prefix !== '' ? "{$prefix}-{$consecutive}" : $consecutive;
    }

    /**
     * Persist invoice items and return [discountAmount, subtotal, vatAmount].
     * subtotal = gross (qty×price) minus discounts, before VAT.
     */
    private function saveItems(Invoice $invoice, array $items): array
    {
        $grossTotal    = 0;
        $discountTotal = 0;
        $vatTotal      = 0;
        $rows          = [];

        foreach ($items as $item) {
            $qty          = (float) $item['quantity'];
            $price        = (float) $item['unit_price'];
            $vatRate      = (float) ($item['vat_rate'] ?? 0);
            $discountRate = (float) ($item['discount_rate'] ?? 0);

            $lineBase      = round($qty * $price, 2);
            $lineDiscount  = round($lineBase * $discountRate / 100, 2);
            $lineAfterDisc = $lineBase - $lineDiscount;
            $lineVat       = round($lineAfterDisc * $vatRate / 100, 2);

            $rows[] = [
                'invoice_id'    => $invoice->id,
                'service_id'    => $item['service_id'] ?? null,
                'description'   => $item['description'],
                'quantity'      => $qty,
                'unit_price'    => $price,
                'discount_rate' => $discountRate,
                'vat_rate'      => $vatRate,
                'subtotal'      => $lineAfterDisc + $lineVat,
                'created_at'    => now(),
                'updated_at'    => now(),
            ];

            $grossTotal    += $lineBase;
            $discountTotal += $lineDiscount;
            $vatTotal      += $lineVat;
        }

        InvoiceItem::insert($rows);

        $subtotal = $grossTotal - $discountTotal;

        return [$discountTotal, $subtotal, $vatTotal];
    }
}

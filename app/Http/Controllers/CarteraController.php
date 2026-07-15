<?php

namespace App\Http\Controllers;

use App\Mail\StatementMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CarteraController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();
        $search = $request->get('search');
        $today  = Carbon::today();

        Invoice::syncOverdueForUser($userId);

        // ── KPIs via una sola query de agregación ─────────────────────────────
        $kpis = DB::selectOne("
            SELECT
                COALESCE(SUM(CASE WHEN i.status IN ('sent','overdue')
                    THEN i.total - COALESCE(p.paid, 0) ELSE 0 END), 0) AS total_por_cobrar,
                COALESCE(SUM(CASE WHEN i.status = 'overdue'
                    THEN i.total - COALESCE(p.paid, 0) ELSE 0 END), 0) AS total_vencida
            FROM invoices i
            LEFT JOIN (
                SELECT invoice_id, SUM(amount) AS paid
                FROM payments
                GROUP BY invoice_id
            ) p ON p.invoice_id = i.id
            WHERE i.user_id = ?
              AND i.status NOT IN ('cancelled', 'draft')
        ", [$userId]);

        $totalPorCobrar = (float) ($kpis->total_por_cobrar ?? 0);
        $totalVencida   = (float) ($kpis->total_vencida ?? 0);

        $cobradoMes = Payment::where('user_id', $userId)
            ->whereBetween('payment_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->sum('amount');

        // ── Antigüedad de cartera via SQL (evita cargar todas las facturas) ───
        $agingRows = DB::select("
            SELECT i.due_date,
                   i.total - COALESCE(p.paid, 0) AS balance
            FROM invoices i
            LEFT JOIN (
                SELECT invoice_id, SUM(amount) AS paid
                FROM payments
                GROUP BY invoice_id
            ) p ON p.invoice_id = i.id
            WHERE i.user_id = ?
              AND i.status IN ('sent', 'overdue')
              AND i.total - COALESCE(p.paid, 0) > 0.01
        ", [$userId]);

        $aging = ['al_dia' => 0.0, 'dias_1_30' => 0.0, 'dias_31_60' => 0.0, 'dias_mas60' => 0.0];
        foreach ($agingRows as $row) {
            $saldo = (float) $row->balance;
            if (! $row->due_date) {
                $aging['al_dia'] += $saldo;
                continue;
            }
            $diff = $today->diffInDays(Carbon::parse($row->due_date), false);
            if ($diff >= 0)       $aging['al_dia']     += $saldo;
            elseif ($diff >= -30) $aging['dias_1_30']  += $saldo;
            elseif ($diff >= -60) $aging['dias_31_60'] += $saldo;
            else                  $aging['dias_mas60'] += $saldo;
        }

        // ── Resumen por cliente via JOIN SQL con búsqueda a nivel de BD ───────
        $sql = "
            SELECT
                c.id, c.name, c.document_number, c.document_type, c.dv, c.email,
                COALESCE(SUM(i.total), 0) AS total_invoiced,
                COALESCE(SUM(COALESCE(p.paid, 0)), 0) AS total_paid,
                COALESCE(SUM(
                    CASE WHEN i.status IN ('sent','overdue')
                        THEN CASE WHEN i.total - COALESCE(p.paid,0) > 0
                             THEN i.total - COALESCE(p.paid,0)
                             ELSE 0 END
                    ELSE 0 END
                ), 0) AS balance,
                COUNT(i.id) AS count_total,
                SUM(CASE WHEN i.status IN ('sent','overdue') THEN 1 ELSE 0 END) AS count_pending,
                SUM(CASE WHEN i.status = 'overdue' THEN 1 ELSE 0 END) AS count_overdue,
                MIN(CASE WHEN i.status = 'overdue' THEN i.due_date END) AS oldest_overdue
            FROM clients c
            INNER JOIN invoices i
                ON i.client_id = c.id
               AND i.status NOT IN ('cancelled', 'draft')
               AND i.user_id = ?
            LEFT JOIN (
                SELECT invoice_id, SUM(amount) AS paid
                FROM payments
                GROUP BY invoice_id
            ) p ON p.invoice_id = i.id
            WHERE c.user_id = ?
        ";
        $bindings = [$userId, $userId];

        if ($search) {
            $sql     .= " AND (c.name LIKE ? OR c.document_number LIKE ?)";
            $bindings[] = "%{$search}%";
            $bindings[] = "%{$search}%";
        }

        $sql .= "
            GROUP BY c.id, c.name, c.document_number, c.document_type, c.dv
            ORDER BY balance DESC
        ";

        $rawRows = DB::select($sql, $bindings);

        $clientSummaries = collect($rawRows)->map(function ($row) use ($today) {
            $balance     = max(0.0, (float) $row->balance);
            $daysOverdue = 0;
            if ($row->oldest_overdue) {
                $daysOverdue = max(
                    0,
                    (int) ($today->diffInDays(Carbon::parse($row->oldest_overdue), false) * -1)
                );
            }

            // Hidratamos un Client real para que route() funcione con model binding
            $client = new Client();
            $client->setRawAttributes([
                'id'              => $row->id,
                'name'            => $row->name,
                'document_number' => $row->document_number,
                'document_type'   => $row->document_type,
                'dv'              => $row->dv,
                'email'           => $row->email,
            ], true);

            return [
                'client'         => $client,
                'total_invoiced' => (float) $row->total_invoiced,
                'total_paid'     => (float) $row->total_paid,
                'balance'        => $balance,
                'count_total'    => (int) $row->count_total,
                'count_pending'  => (int) $row->count_pending,
                'count_overdue'  => (int) $row->count_overdue,
                'days_overdue'   => $daysOverdue,
            ];
        });

        $clientesPendientes = $clientSummaries->where('balance', '>', 0)->count();

        // ── Modal de pago rápido: solo facturas pendientes con saldo ──────────
        $pendingForModal = Invoice::with([
            'client:id,name,document_type,document_number,dv',
            'payments:invoice_id,amount',
        ])
            ->where('user_id', $userId)
            ->whereIn('status', ['sent', 'overdue'])
            ->get(['id', 'number', 'client_id', 'total', 'status', 'due_date'])
            ->filter(fn($i) => $i->balance > 0)
            ->map(fn($i) => [
                'id'          => $i->id,
                'number'      => $i->number,
                'client_id'   => $i->client_id,
                'client_name' => $i->client->name,
                'client_doc'  => $i->client->full_document,
                'total'       => (float) $i->total,
                'balance'     => (float) max(0, $i->balance),
                'status'      => $i->status,
                'due_date'    => $i->due_date?->format('d/m/Y'),
                'pay_url'     => route('payments.store', $i->id),
            ])
            ->values();

        return view('cartera.index', compact(
            'clientSummaries',
            'totalPorCobrar',
            'totalVencida',
            'cobradoMes',
            'clientesPendientes',
            'aging',
            'search',
            'pendingForModal'
        ));
    }

    public function client(Client $client)
    {
        abort_if($client->user_id !== auth()->id(), 403);

        Invoice::syncOverdueForUser(auth()->id());

        $invoices = Invoice::with(['payments', 'items'])
            ->where('user_id', auth()->id())
            ->where('client_id', $client->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->orderByRaw("CASE status WHEN 'overdue' THEN 0 WHEN 'sent' THEN 1 ELSE 2 END")
            ->orderBy('due_date', 'asc')
            ->get();

        $totalInvoiced = (float) $invoices->sum('total');
        $totalPaid     = (float) $invoices->sum(fn($i) => $i->paid_amount);
        $balance       = (float) $invoices->whereIn('status', ['sent', 'overdue'])
                                          ->sum(fn($i) => max(0, $i->balance));
        $countOverdue  = $invoices->where('status', 'overdue')->count();

        return view('cartera.client', compact(
            'client',
            'invoices',
            'totalInvoiced',
            'totalPaid',
            'balance',
            'countOverdue'
        ));
    }

    public function sendStatementEmail(Request $request, Client $client): RedirectResponse
    {
        abort_if($client->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'email'   => 'required|email|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        $invoices = Invoice::with('payments')
            ->where('user_id', auth()->id())
            ->where('client_id', $client->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $allPayments = $invoices->flatMap(function ($inv) {
            return $inv->payments->map(fn($p) => (object)[
                'payment_date'   => $p->payment_date,
                'invoice_number' => $inv->number,
                'payment_method' => $p->payment_method,
                'reference'      => $p->reference,
                'amount'         => $p->amount,
            ]);
        })->sortBy('payment_date')->values();

        Mail::to($data['email'])->send(
            new StatementMail($client, auth()->user(), $invoices, $allPayments, $data['message'] ?? null)
        );

        return back()->with('success', "Estado de cuenta de {$client->name} enviado a {$data['email']}.");
    }

    public function printStatement(Client $client)
    {
        abort_if($client->user_id !== auth()->id(), 403);

        $invoices = Invoice::with('payments')
            ->where('user_id', auth()->id())
            ->where('client_id', $client->id)
            ->whereNotIn('status', ['cancelled', 'draft'])
            ->orderBy('issue_date')
            ->orderBy('id')
            ->get();

        $allPayments = $invoices->flatMap(function ($inv) {
            return $inv->payments->map(fn($p) => (object)[
                'payment_date'   => $p->payment_date,
                'invoice_number' => $inv->number,
                'payment_method' => $p->payment_method,
                'reference'      => $p->reference,
                'amount'         => $p->amount,
            ]);
        })->sortBy('payment_date')->values();

        $user = auth()->user();

        return view('cartera.statement-print', compact('client', 'invoices', 'allPayments', 'user'));
    }

    public function show(Invoice $invoice)
    {
        abort_if($invoice->user_id !== auth()->id(), 403);

        $invoice->load([
            'client',
            'items.service',
            'payments' => fn($q) => $q->orderBy('payment_date', 'desc')->orderBy('created_at', 'desc'),
        ]);

        return view('cartera.show', compact('invoice'));
    }
}

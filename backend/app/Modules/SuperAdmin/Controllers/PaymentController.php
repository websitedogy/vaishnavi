<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SuperAdmin\Models\Payment;
use App\Modules\SuperAdmin\Models\Refund;
use App\Modules\SuperAdmin\Services\PaymentGatewayService;
use App\Shared\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    protected PaymentGatewayService $paymentService;

    public function __construct(PaymentGatewayService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $query = Payment::with(['tenant', 'invoice', 'refunds']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where('transaction_reference', 'like', "%{$s}%")
                  ->orWhereHas('tenant', fn($q) => $q->where('name', 'like', "%{$s}%"));
        }

        $payments = $query->orderByDesc('id')->paginate($request->integer('per_page', 15));

        return $this->successResponse($payments);
    }

    public function show(int $id)
    {
        $payment = Payment::with(['tenant', 'invoice', 'refunds'])->findOrFail($id);
        $transactions = DB::table('payment_transactions')->where('payment_id', $id)->get();

        return $this->successResponse([
            'payment' => $payment,
            'transactions' => $transactions,
        ]);
    }

    public function refund(Request $request, int $id)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'reason' => 'required|string|max:500',
        ]);

        $adminId = $request->user() ? $request->user()->id : null;
        $refund = $this->paymentService->processRefund($id, floatval($validated['amount']), $validated['reason'], $adminId);

        return $this->successResponse($refund, 'Refund processed successfully.');
    }

    public function webhook(Request $request, string $gateway)
    {
        $payload = $request->all();
        $signature = $request->header('X-Razorpay-Signature') ?? $request->header('Stripe-Signature');

        $result = $this->paymentService->handleWebhook($gateway, $payload, $signature);

        return response()->json($result);
    }

    public function reconciliation(Request $request)
    {
        $logs = DB::table('payment_reconciliation_logs')
            ->orderByDesc('period_end')
            ->paginate($request->integer('per_page', 15));

        return $this->successResponse($logs);
    }

    public function revenueSummary()
    {
        $totalRevenue = Payment::where('status', 'success')->sum('amount');
        $totalTax = Payment::where('status', 'success')->sum('tax');
        $totalRefunds = Refund::where('status', 'completed')->sum('amount');
        $netRevenue = $totalRevenue - $totalRefunds;

        $gatewayDistribution = Payment::where('status', 'success')
            ->select('gateway', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(id) as count'))
            ->groupBy('gateway')
            ->get();

        return $this->successResponse([
            'total_revenue' => round($totalRevenue, 2),
            'total_tax' => round($totalTax, 2),
            'total_refunds' => round($totalRefunds, 2),
            'net_revenue' => round($netRevenue, 2),
            'gateway_breakdown' => $gatewayDistribution,
        ]);
    }
}

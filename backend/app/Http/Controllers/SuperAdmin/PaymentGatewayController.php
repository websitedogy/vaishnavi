<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentGatewayController extends Controller
{
    public function index()
    {
        $gateways = PaymentGateway::orderBy('name')->get();

        return view('super-admin.payments.index', compact('gateways'));
    }

    public function update(Request $request, PaymentGateway $gateway)
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in(['test', 'live'])],
            'merchant_key' => ['nullable', 'string', 'max:255'],
            'secret_key' => ['nullable', 'string', 'max:255'],
            'webhook_secret' => ['nullable', 'string', 'max:255'],
        ]);

        $gateway->update([
            ...$data,
            'is_enabled' => $request->boolean('is_enabled'),
        ]);

        return back()->with('success', $gateway->name.' settings updated.');
    }
}

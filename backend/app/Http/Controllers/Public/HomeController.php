<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\RegisterTenantRequest;
use App\Services\TenantRegistrationService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __construct(
        protected TenantRegistrationService $registration
    ) {}

    public function index(Request $request)
    {
        return view('public.index', [
            'plans' => [
                'basic' => ['label' => 'Starter', 'price' => 29, 'desc' => 'Perfect to launch your local news site'],
                'standard' => ['label' => 'Growth', 'price' => 79, 'desc' => 'More traffic, ads & team members'],
                'premium' => ['label' => 'Pro', 'price' => 149, 'desc' => 'Priority support & advanced analytics'],
            ],
            'registered_email' => $request->session()->get('registered_email'),
        ]);
    }

    public function register(RegisterTenantRequest $request)
    {
        $tenant = $this->registration->register($request->validated());

        return redirect()
            ->route('tenant-admin.login')
            ->with('success', "Welcome aboard, {$tenant->name}! Your 14-day trial is active.")
            ->with('registered_email', $request->input('admin_email'));
    }
}

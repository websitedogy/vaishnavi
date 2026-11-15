<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\Reader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ReaderController extends Controller
{
    use ResolvesTenant;

    public function index(Request $request)
    {
        $readers = Reader::where('tenant_id', $this->tenantId())
            ->when($request->premium, fn ($q) => $q->where('is_premium', true))
            ->when($request->banned, fn ($q) => $q->where('is_banned', true))
            ->latest()->paginate(20);

        return view('tenant-admin.readers.index', compact('readers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:readers,email,NULL,id,tenant_id,'.$this->tenantId(),
            'password' => 'nullable|string|min:8',
            'is_premium' => 'boolean',
        ]);
        $data['tenant_id'] = $this->tenantId();
        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        $data['is_premium'] = $request->boolean('is_premium');
        Reader::create($data);

        return back()->with('success', 'Reader account created.');
    }

    public function ban(Reader $reader)
    {
        abort_unless($reader->tenant_id === $this->tenantId(), 404);
        $reader->update(['is_banned' => ! $reader->is_banned]);

        return back()->with('success', $reader->is_banned ? 'User banned.' : 'Ban lifted.');
    }
}

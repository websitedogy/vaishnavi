<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\Poll;
use Illuminate\Http\Request;

class PollController extends Controller
{
    use ResolvesTenant;

    public function index()
    {
        $polls = Poll::where('tenant_id', $this->tenantId())->latest()->paginate(12);

        return view('tenant-admin.polls.index', compact('polls'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'question' => 'required|string|max:500',
            'options' => 'required|string',
            'closes_at' => 'nullable|date',
        ]);
        $options = array_values(array_filter(array_map('trim', explode("\n", $data['options']))));
        $votes = array_fill_keys($options, 0);
        Poll::create([
            'tenant_id' => $this->tenantId(),
            'question' => $data['question'],
            'options' => $options,
            'votes' => $votes,
            'is_active' => true,
            'closes_at' => $data['closes_at'] ?? null,
        ]);

        return back()->with('success', 'Poll created.');
    }

    public function destroy(Poll $poll)
    {
        abort_unless($poll->tenant_id === $this->tenantId(), 404);
        $poll->delete();

        return back()->with('success', 'Poll removed.');
    }
}

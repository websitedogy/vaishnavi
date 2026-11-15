@extends('layouts.tenant-admin')
@section('page-title','Polls & Engagement')
@section('content')
<form method="POST" action="{{ route('tenant-admin.polls.store') }}" class="bg-white rounded-xl border p-5 mb-6 max-w-xl space-y-3">@csrf
<input name="question" required class="w-full rounded-lg border-slate-300" placeholder="Poll question">
<textarea name="options" required rows="4" class="w-full rounded-lg border-slate-300 text-sm" placeholder="One option per line"></textarea>
<input type="datetime-local" name="closes_at" class="w-full rounded-lg border-slate-300 text-sm">
<button class="px-4 py-2 bg-brand-600 text-white text-sm rounded-lg">Create poll</button>
</form>
<p class="text-sm text-slate-500 mb-4">Surveys & reactions: use polls for quick engagement; article likes/dislikes tracked via reactions table on publish.</p>
@foreach($polls as $poll)<div class="bg-white border rounded-xl p-4 mb-3 flex justify-between"><div><p class="font-medium">{{ $poll->question }}</p><p class="text-sm text-slate-500">{{ $poll->totalVotes() }} votes · {{ $poll->is_active ? 'Active' : 'Closed' }}</p></div>
<form method="POST" action="{{ route('tenant-admin.polls.destroy',$poll) }}">@csrf @method('DELETE')<button class="text-rose-600 text-sm">Remove</button></form></div>@endforeach
{{ $polls->links() }}
@endsection

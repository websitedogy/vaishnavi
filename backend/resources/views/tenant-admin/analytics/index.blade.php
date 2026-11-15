@extends('layouts.tenant-admin')
@section('page-title','Analytics')
@section('content')
<form method="GET" class="mb-4"><select name="days" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-sm">@foreach([7,14,30] as $d)<option value="{{ $d }}" @selected($days===$d)>Last {{ $d }} days</option>@endforeach</select></form>
<div class="grid sm:grid-cols-4 gap-4 mb-6">
<div class="bg-white rounded-xl border p-4"><p class="text-xs text-slate-500">Revenue</p><p class="text-xl font-bold text-emerald-600">${{ number_format($revenue,2) }}</p></div>
<div class="bg-white rounded-xl border p-4"><p class="text-xs text-slate-500">Traffic records</p><p class="text-xl font-bold">{{ $traffic->count() }}</p></div>
</div>
<canvas id="chart" height="100" class="bg-white rounded-xl border p-4 mb-6"></canvas>
<div class="grid lg:grid-cols-2 gap-6">
<div class="bg-white rounded-xl border p-5"><h3 class="font-semibold mb-3">Article performance</h3>
<ul class="text-sm space-y-2">@foreach($topArticles as $a)<li class="flex justify-between"><span class="truncate pr-2">{{ $a->title }}</span><span>{{ number_format($a->views) }} views</span></li>@endforeach</ul></div>
<div class="bg-white rounded-xl border p-5 text-sm text-slate-600">
<p><strong>Page views</strong> — tracked per article</p>
<p class="mt-2"><strong>User sessions / bounce / devices / countries / sources</strong> — connect Google Analytics ID in SEO settings for full breakdown.</p>
</div></div>
@endsection
@push('scripts')<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script><script>
new Chart(document.getElementById('chart'),{type:'bar',data:{labels:@json($chart['labels']),datasets:[{label:'Page views',data:@json($chart['views'])},{label:'Visitors',data:@json($chart['visitors'])}]}});
</script>@endpush

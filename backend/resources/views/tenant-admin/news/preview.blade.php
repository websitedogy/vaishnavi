<!DOCTYPE html>
<html lang="{{ $news->locale }}"><head><meta charset="UTF-8"><title>{{ $news->title }} — Preview</title><script src="https://cdn.tailwindcss.com"></script></head>
<body class="bg-slate-50 p-8 max-w-3xl mx-auto">
<p class="text-sm text-amber-700 bg-amber-50 inline-block px-2 py-1 rounded mb-4">Article preview — not public</p>
@if($news->is_breaking)<p class="text-rose-600 font-bold text-sm mb-2">BREAKING NEWS</p>@endif
@if($news->is_trending)<p class="text-violet-600 font-semibold text-sm mb-2">TRENDING</p>@endif
<h1 class="text-3xl font-bold text-slate-900">{{ $news->custom_title ?: $news->title }}</h1>
@if($news->custom_title)<p class="text-sm text-slate-500 mt-1">Original: {{ $news->title }}</p>@endif
@if($news->subtitle)<p class="text-xl text-slate-600 mt-2">{{ $news->subtitle }}</p>@endif
<p class="text-slate-500 text-sm mt-2">{{ $news->published_at?->format('M d, Y') ?? 'Draft' }} · {{ strtoupper($news->locale) }}</p>
@if($news->featured_image)<img src="{{ \App\Models\MediaFile::normalizeStoredUrl($news->featured_image) }}" alt="" class="mt-4 rounded-xl w-full max-h-80 object-cover">@endif
<div class="prose mt-6">{!! $news->content !!}</div>
<p class="mt-8 flex flex-wrap gap-3">
@if($news->exists ?? false)
<a href="{{ route('tenant-admin.news.edit', $news->id) }}" class="text-brand-600 font-medium">← Back to editor</a>
@else
<button type="button" onclick="window.close()" class="text-brand-600 font-medium">← Close preview</button>
@endif
</p>
</body></html>

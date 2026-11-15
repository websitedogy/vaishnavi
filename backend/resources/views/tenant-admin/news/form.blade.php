@extends('layouts.tenant-admin')
@php $editing = $article->exists; @endphp
@section('title', $editing ? 'Edit article' : 'New article')
@section('page-title', $editing ? 'Edit article' : 'Create article')
@section('content')
<form method="POST" action="{{ $editing ? route('tenant-admin.news.update', $article) : route('tenant-admin.news.store') }}" id="articleForm">
@csrf @if($editing) @method('PUT') @endif
<div class="grid lg:grid-cols-3 gap-6">
<div class="lg:col-span-2 space-y-4">
<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-4">
<input type="text" name="title" id="title" value="{{ old('title', $article->title) }}" required placeholder="Headline" class="w-full text-xl font-semibold border-0 border-b border-slate-200 focus:ring-0 px-0">
<textarea name="excerpt" rows="2" placeholder="Excerpt" class="w-full rounded-lg border-slate-300 text-sm">{{ old('excerpt', $article->excerpt) }}</textarea>
<div id="editor" class="min-h-[280px] bg-white"></div>
<textarea name="content" id="content" class="hidden">{{ old('content', $article->content) }}</textarea>
</div>
<div class="bg-white rounded-xl border border-slate-200 p-5">
<h3 class="font-semibold mb-3">SEO meta</h3>
<input type="text" name="meta_title" value="{{ old('meta_title', $article->meta_title) }}" placeholder="Meta title" class="w-full mb-2 rounded-lg border-slate-300 text-sm">
<textarea name="meta_description" rows="2" placeholder="Meta description" class="w-full rounded-lg border-slate-300 text-sm">{{ old('meta_description', $article->meta_description) }}</textarea>
<label class="mt-2 flex items-center gap-2 text-sm"><input type="checkbox" name="regenerate_slug" value="1"> Regenerate slug from title</label>
</div>
</div>
<div class="space-y-4">
<div class="bg-white rounded-xl border border-slate-200 p-5 space-y-3">
<label class="block text-sm font-medium">Status</label>
<select name="status" class="w-full rounded-lg border-slate-300 text-sm">@foreach(['draft','scheduled','published','archived'] as $s)<option value="{{ $s }}" @selected(old('status',$article->status)===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
<label class="block text-sm font-medium">Schedule publish</label>
<input type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at', $article->scheduled_at?->format('Y-m-d\TH:i')) }}" class="w-full rounded-lg border-slate-300 text-sm">
<label class="block text-sm font-medium">Category</label>
<select name="category_id" class="w-full rounded-lg border-slate-300 text-sm"><option value="">— None —</option>
@foreach($categories->whereNull('parent_id') as $parent)
<option value="{{ $parent->id }}" @selected(old('category_id',$article->category_id)==$parent->id)>{{ $parent->name }}</option>
@foreach($categories->where('parent_id',$parent->id) as $sub)
<option value="{{ $sub->id }}" @selected(old('category_id',$article->category_id)==$sub->id)>&nbsp;&nbsp;↳ {{ $sub->name }}</option>
@endforeach
@endforeach
@foreach($categories->whereNotNull('parent_id')->filter(fn($c) => !$categories->pluck('id')->contains($c->parent_id)) as $orphan)
<option value="{{ $orphan->id }}" @selected(old('category_id',$article->category_id)==$orphan->id)>{{ $orphan->name }}</option>
@endforeach
</select>
<label class="block text-sm font-medium">Tags (comma separated)</label>
<input type="text" name="tags" value="{{ old('tags', $article->exists ? $article->tags->pluck('name')->join(', ') : '') }}" class="w-full rounded-lg border-slate-300 text-sm">
<label class="block text-sm font-medium">Language</label>
<select name="locale" class="w-full rounded-lg border-slate-300 text-sm">@foreach($locales as $k=>$l)<option value="{{ $k }}" @selected(old('locale',$article->locale)===$k)>{{ $l }}</option>@endforeach</select>
<label class="block text-sm font-medium">Featured image URL</label>
<input type="text" name="featured_image" value="{{ old('featured_image', $article->featured_image) }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="/storage/...">
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_breaking" value="1" @checked(old('is_breaking',$article->is_breaking))> Breaking news</label>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_featured" value="1" @checked(old('is_featured',$article->is_featured))> Featured article</label>
<button type="submit" class="w-full py-2.5 bg-brand-600 text-white font-semibold rounded-lg">Save article</button>
@if($editing)<a href="{{ route('tenant-admin.news.preview',$article) }}" target="_blank" class="block text-center text-sm text-brand-600 mt-2">Preview article</a>@endif
</div>
</div>
</div>
</form>
<p class="text-xs text-slate-500 mt-2" id="autosaveStatus">Auto-save enabled</p>
@endsection
@push('styles')
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet">
@endpush
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>
<script>
const quill=new Quill('#editor',{theme:'snow'});
quill.root.innerHTML=document.getElementById('content').value;
document.getElementById('articleForm').addEventListener('submit',()=>{document.getElementById('content').value=quill.root.innerHTML;});
setInterval(()=>{
const fd=new FormData();fd.append('title',document.getElementById('title').value);fd.append('content',quill.root.innerHTML);fd.append('excerpt',document.querySelector('[name=excerpt]').value);fd.append('_token','{{ csrf_token() }}');
fetch('{{ $editing ? route('tenant-admin.news.autosave',$article) : route('tenant-admin.news.autosave') }}',{method:'POST',body:fd}).then(()=>document.getElementById('autosaveStatus').textContent='Draft auto-saved '+new Date().toLocaleTimeString());
},45000);
</script>
@endpush

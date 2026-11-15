<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\MediaFile;
use App\Services\StaffActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MediaController extends Controller
{
    use ResolvesTenant;

    public function index(Request $request)
    {
        $this->authorizePermission('manage_media');
        $query = MediaFile::where('tenant_id', $this->tenantId());

        if ($search = $request->get('q')) {
            $query->where('name', 'like', "%{$search}%");
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        $files = $query->latest()->paginate(24)->withQueryString();
        $storageUsed = MediaFile::where('tenant_id', $this->tenantId())->sum('size');

        return view('tenant-admin.media.index', compact('files', 'storageUsed'));
    }

    public function store(Request $request)
    {
        $this->authorizePermission('manage_media');
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'file|max:20480',
        ]);

        $uploaded = [];
        foreach ($request->file('files', []) as $file) {
            $uploaded[] = $this->persistUpload($file);
        }

        StaffActivityLogger::log('media_uploaded', 'Uploaded media file(s)');

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => count($uploaded).' file(s) uploaded.',
                'count' => count($uploaded),
            ]);
        }

        return back()->with('success', count($uploaded).' file(s) uploaded.');
    }

    public function reupload(Request $request, MediaFile $media)
    {
        $this->ensureTenant($media);
        $this->authorizePermission('manage_media');

        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $disk = $media->disk ?: 'public';

        Storage::disk($disk)->delete(array_filter([$media->path, $media->thumbnail_path]));

        $path = $file->store('tenants/'.$this->tenantId().'/media', 'public');
        $mime = $file->getMimeType();
        $type = str_starts_with($mime, 'video/') ? 'video' : (str_contains($mime, 'pdf') ? 'pdf' : 'image');

        $media->update([
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'type' => $type,
            'mime_type' => $mime,
            'size' => $file->getSize(),
            'thumbnail_path' => $type === 'image' ? $path : null,
        ]);

        StaffActivityLogger::log('media_reuploaded', "Replaced media: {$media->name}", $media);

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'url' => $media->fresh()->url(),
                'name' => $media->name,
            ]);
        }

        return back()->with('success', 'File replaced successfully.');
    }

    public function destroy(Request $request, MediaFile $medium)
    {
        $this->ensureTenant($medium);
        $this->authorizePermission('manage_media');

        $disk = $medium->disk ?: 'public';
        $paths = array_filter([$medium->path, $medium->thumbnail_path]);

        if ($paths !== []) {
            Storage::disk($disk)->delete($paths);
        }

        $name = $medium->name;
        $medium->delete();

        StaffActivityLogger::log('media_deleted', "Deleted media: {$name}");

        if ($request->ajax() || $request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json(['ok' => true, 'message' => 'File removed from library.']);
        }

        return back()->with('success', 'File removed from library and storage.');
    }

    /** JSON list for post editor / TinyMCE media picker */
    public function browser(Request $request)
    {
        $this->authorizeEditorMedia();

        $query = MediaFile::where('tenant_id', $this->tenantId())->where('type', 'image');

        if ($search = $request->get('q')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $files = $query->latest()->paginate(24);

        return response()->json([
            'data' => $files->map(fn (MediaFile $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'url' => $f->url(),
                'thumb' => $f->url(),
                'alt' => $f->alt_text ?? $f->name,
            ]),
            'meta' => [
                'current_page' => $files->currentPage(),
                'last_page' => $files->lastPage(),
                'total' => $files->total(),
            ],
        ]);
    }

    /** Single image upload from TinyMCE / post editor */
    public function uploadEditor(Request $request)
    {
        $this->authorizeEditorMedia();

        try {
            $request->validate([
                'file' => 'required|file|image|max:20480',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => $e->validator->errors()->first('file') ?? 'Invalid file.',
            ], 422);
        }

        $media = $this->persistUpload($request->file('file'));

        return response()->json([
            'location' => $media->url(),
            'id' => $media->id,
            'name' => $media->name,
        ]);
    }

    protected function persistUpload($file): MediaFile
    {
        $dir = 'tenants/'.$this->tenantId().'/media';
        $path = $file->store($dir, 'public');
        $mime = $file->getMimeType();
        $type = str_starts_with($mime, 'video/') ? 'video' : (str_contains($mime, 'pdf') ? 'pdf' : 'image');

        return MediaFile::create([
            'tenant_id' => $this->tenantId(),
            'user_id' => $this->user()->id,
            'name' => $file->getClientOriginalName(),
            'path' => $path,
            'disk' => 'public',
            'type' => $type,
            'mime_type' => $mime,
            'size' => $file->getSize(),
            'thumbnail_path' => $type === 'image' ? $path : null,
        ]);
    }

    protected function authorizeEditorMedia(): void
    {
        abort_unless(
            $this->user()->hasPermission('add_news') || $this->user()->hasPermission('manage_media'),
            403,
            'You do not have permission to use the media library.'
        );
    }

    protected function ensureTenant(MediaFile $file): void
    {
        abort_unless($file->tenant_id === $this->tenantId(), 404);
    }
}

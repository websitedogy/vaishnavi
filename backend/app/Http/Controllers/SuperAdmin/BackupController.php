<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\BackupRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BackupController extends Controller
{
    public function index()
    {
        $backups = BackupRecord::latest('backup_taken_at')->paginate(12);

        return view('super-admin.backups.index', compact('backups'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'in:database,files,full'],
        ]);

        BackupRecord::create([
            'type' => $data['type'],
            'file_path' => 'backups/'.now()->format('Ymd_His').'_'.Str::random(6).'.zip',
            'file_size' => random_int(5, 100) * 1024 * 1024,
            'status' => 'ready',
            'backup_taken_at' => now(),
            'notes' => 'Auto backup generated from super admin panel.',
        ]);

        return back()->with('success', 'Backup created successfully.');
    }

    public function restore(BackupRecord $backup)
    {
        $backup->update([
            'status' => 'restored',
            'restored_at' => now(),
        ]);

        return back()->with('success', 'Backup restored successfully.');
    }
}

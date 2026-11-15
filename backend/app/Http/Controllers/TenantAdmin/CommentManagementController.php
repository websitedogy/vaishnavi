<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\ArticleComment;
use App\Models\BlockedCommentUser;
use App\Services\TenantSettingsService;
use Illuminate\Http\Request;

class CommentManagementController extends Controller
{
    use ResolvesTenant;

    public function __construct(protected TenantSettingsService $settings) {}

    public function index(Request $request)
    {
        $config = $this->settings->get($this->tenantId(), 'comments');
        $query = ArticleComment::with('post:id,title')->where('tenant_id', $this->tenantId());

        if ($request->get('queue') === 'pending') {
            $query->where('is_approved', false)->where('is_spam', false);
        } elseif ($request->get('queue') === 'spam') {
            $query->where('is_spam', true);
        }

        $comments = $query->latest()->paginate(20)->withQueryString();
        $blocked = BlockedCommentUser::where('tenant_id', $this->tenantId())->latest()->limit(10)->get();

        return view('tenant-admin.comments.index', compact('comments', 'config', 'blocked'));
    }

    public function approve(ArticleComment $comment)
    {
        $this->ensureTenant($comment);
        $comment->update(['is_approved' => true, 'is_spam' => false]);

        return back()->with('success', 'Comment approved.');
    }

    public function destroy(ArticleComment $comment)
    {
        $this->ensureTenant($comment);
        $comment->delete();

        return back()->with('success', 'Comment deleted.');
    }

    public function blockUser(Request $request)
    {
        $data = $request->validate([
            'email' => 'nullable|email',
            'ip_address' => 'nullable|string|max:45',
            'reason' => 'nullable|string|max:255',
        ]);
        $data['tenant_id'] = $this->tenantId();
        BlockedCommentUser::create($data);

        return back()->with('success', 'User/IP blocked.');
    }

    public function updateSettings(Request $request)
    {
        $this->settings->save($this->tenantId(), 'comments', $request->only([
            'comments_enabled', 'moderation_queue', 'spam_keywords', 'auto_flag_spam',
        ]));

        return back()->with('success', 'Comment settings saved.');
    }

    protected function ensureTenant(ArticleComment $comment): void
    {
        abort_unless($comment->tenant_id === $this->tenantId(), 404);
    }
}

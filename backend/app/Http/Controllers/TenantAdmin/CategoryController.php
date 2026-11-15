<?php

namespace App\Http\Controllers\TenantAdmin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\TenantAdmin\Concerns\ResolvesTenant;
use App\Models\Category;
use App\Models\NewsPost;
use App\Support\CategoryParentColors;
use App\Services\StaffActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    use ResolvesTenant;

    public function index()
    {
        $this->authorizePermission('manage_categories');

        $categories = Category::with(['children' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount(['posts', 'children'])
            ->where('tenant_id', $this->tenantId())
            ->parents()
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(15);

        $totalPosts = NewsPost::where('tenant_id', $this->tenantId())->count();

        return view('tenant-admin.categories.index', compact('categories', 'totalPosts'));
    }

    public function subcategoriesIndex(Request $request)
    {
        $this->authorizePermission('manage_categories');

        $parentId = $request->get('parent_id');

        $query = Category::query()
            ->with('parent')
            ->withCount('posts')
            ->where('categories.tenant_id', $this->tenantId())
            ->subcategories()
            ->join('categories as parent_cat', 'categories.parent_id', '=', 'parent_cat.id')
            ->orderBy('parent_cat.name')
            ->orderBy('categories.name')
            ->select('categories.*');

        if ($parentId) {
            $query->where('categories.parent_id', $parentId);
        }

        $subcategories = $query->paginate(12)->withQueryString();
        $parents = Category::where('tenant_id', $this->tenantId())->parents()->orderBy('name')->get();
        $selectedParent = $parentId ? $parents->firstWhere('id', (int) $parentId) : null;
        $totalSubcategories = Category::where('tenant_id', $this->tenantId())->subcategories()->count();

        $subcategoryIdsQuery = Category::where('tenant_id', $this->tenantId())->subcategories();
        if ($parentId) {
            $subcategoryIdsQuery->where($subcategoryIdsQuery->qualifyColumn('parent_id'), $parentId);
        }
        $totalPosts = NewsPost::where('tenant_id', $this->tenantId())
            ->whereIn('category_id', $subcategoryIdsQuery->pluck('id'))
            ->count();

        $parentColorMap = CategoryParentColors::map($parents);

        return view('tenant-admin.categories.subcategories', compact(
            'subcategories',
            'parents',
            'selectedParent',
            'totalSubcategories',
            'totalPosts',
            'parentColorMap',
            'parentId'
        ));
    }

    public function create()
    {
        $this->authorizePermission('manage_categories');

        return view('tenant-admin.categories.form', [
            'category' => new Category(['is_visible' => true, 'sort_order' => 0]),
            'type' => 'parent',
            'parents' => collect(),
        ]);
    }

    public function createSubcategory(Request $request)
    {
        $this->authorizePermission('manage_categories');

        return view('tenant-admin.categories.form', [
            'category' => new Category([
                'is_visible' => true,
                'sort_order' => 0,
                'parent_id' => $request->get('parent_id'),
            ]),
            'type' => 'subcategory',
            'parents' => $this->parentOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizePermission('manage_categories');
        $isSubcategory = $request->boolean('is_subcategory') || $request->filled('parent_id');
        $data = $this->validated($request, null, $isSubcategory);
        $data['tenant_id'] = $this->tenantId();
        $data['slug'] = Category::uniqueSlug($this->tenantId(), $data['name']);

        if (! $isSubcategory) {
            $data['parent_id'] = null;
        }

        $data = $this->handleUploads($request, $data);
        $cat = Category::create($data);

        $label = $isSubcategory ? 'Subcategory' : 'Category';
        StaffActivityLogger::log('category_created', "{$label}: {$cat->name}", $cat);

        return redirect()->to($isSubcategory
            ? route('tenant-admin.categories.subcategories.index', ['parent_id' => $cat->parent_id])
            : route('tenant-admin.categories.index')
        )->with('success', "{$label} created successfully.");
    }

    public function edit(Category $category)
    {
        $this->ensureTenant($category);
        $this->authorizePermission('manage_categories');

        return view('tenant-admin.categories.form', [
            'category' => $category,
            'type' => $category->isSubcategory() ? 'subcategory' : 'parent',
            'parents' => $this->parentOptions($category->id),
        ]);
    }

    public function update(Request $request, Category $category)
    {
        $this->ensureTenant($category);
        $this->authorizePermission('manage_categories');

        $isSubcategory = $category->isSubcategory() || $request->boolean('is_subcategory');
        $data = $this->validated($request, $category->id, $isSubcategory);

        if (! $isSubcategory) {
            $data['parent_id'] = null;
        }

        if ($request->boolean('regenerate_slug')) {
            $data['slug'] = Category::uniqueSlug($this->tenantId(), $data['name'], $category->id);
        }

        $data = $this->handleUploads($request, $data, $category);
        $category->update($data);
        StaffActivityLogger::log('category_updated', "Category: {$category->name}", $category);

        return redirect()->to($category->isSubcategory()
            ? route('tenant-admin.categories.subcategories.index', ['parent_id' => $category->parent_id])
            : route('tenant-admin.categories.index')
        )->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        $this->ensureTenant($category);
        $this->authorizePermission('manage_categories');

        if ($category->isParent() && $category->children()->exists()) {
            return back()->with('error', 'Delete or reassign subcategories before removing this category.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted.');
    }

    public function toggleVisibility(Category $category)
    {
        $this->ensureTenant($category);
        $this->authorizePermission('manage_categories');
        $category->update(['is_visible' => ! $category->is_visible]);

        return back()->with('success', 'Visibility updated.');
    }

    /** Inline category create from post editor sidebar */
    public function quickStore(Request $request)
    {
        abort_unless(
            $this->user()->hasPermission('manage_categories') || $this->user()->hasPermission('add_news'),
            403
        );

        $tenantId = $this->tenantId();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('parent_id')),
            ],
        ]);

        $isSub = ! empty($data['parent_id']);

        $cat = Category::create([
            'tenant_id' => $tenantId,
            'parent_id' => $isSub ? $data['parent_id'] : null,
            'name' => $data['name'],
            'slug' => Category::uniqueSlug($tenantId, $data['name']),
            'is_visible' => true,
            'sort_order' => 0,
        ]);

        StaffActivityLogger::log('category_created', ($isSub ? 'Subcategory' : 'Category').": {$cat->name}", $cat);

        return response()->json([
            'category' => [
                'id' => $cat->id,
                'name' => $cat->name,
                'parent_id' => $cat->parent_id,
                'is_sub' => $isSub,
            ],
        ]);
    }

    public function reorder(Request $request)
    {
        $this->authorizePermission('manage_categories');

        foreach ($request->input('order', []) as $sort => $id) {
            Category::where('tenant_id', $this->tenantId())->where('id', $id)->update(['sort_order' => $sort]);
        }

        return response()->json(['ok' => true]);
    }

    protected function validated(Request $request, ?int $exceptId = null, bool $requireParent = false): array
    {
        $tenantId = $this->tenantId();

        $parentRule = [
            'nullable',
            Rule::exists('categories', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('parent_id')),
        ];

        if ($requireParent) {
            $parentRule = [
                'required',
                Rule::exists('categories', 'id')->where(fn ($q) => $q->where('tenant_id', $tenantId)->whereNull('parent_id')),
            ];
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => $parentRule,
            'description' => 'nullable|string',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:500',
            'sort_order' => 'integer|min:0',
            'icon' => 'nullable|image|max:1024',
            'image' => 'nullable|image|max:2048',
        ]);

        // Checkbox: unchecked = not sent — must be false (not default true).
        $data['is_visible'] = $request->has('is_visible');

        return $data;
    }

    protected function handleUploads(Request $request, array $data, ?Category $existing = null): array
    {
        $dir = 'tenants/'.$this->tenantId().'/categories';
        if ($request->hasFile('icon')) {
            if ($existing?->icon) {
                Storage::disk('public')->delete($existing->icon);
            }
            $data['icon'] = $request->file('icon')->store($dir, 'public');
        }
        if ($request->hasFile('image')) {
            if ($existing?->image) {
                Storage::disk('public')->delete($existing->image);
            }
            $data['image'] = $request->file('image')->store($dir, 'public');
        }

        return $data;
    }

    protected function parentOptions(?int $exceptId = null)
    {
        return Category::where('tenant_id', $this->tenantId())
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->parents()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function ensureTenant(Category $category): void
    {
        abort_unless($category->tenant_id === $this->tenantId(), 404);
    }

}

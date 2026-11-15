<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySubcategorySeeder extends Seeder
{
    /** Default subcategory labels created under each parent that has none yet. */
    protected array $subcategoryTemplates = [
        ['suffix' => 'breaking', 'label' => 'Breaking'],
        ['suffix' => 'latest', 'label' => 'Latest News'],
        ['suffix' => 'exclusive', 'label' => 'Exclusive'],
        ['suffix' => 'analysis', 'label' => 'Analysis'],
    ];

    public function run(): void
    {
        $created = 0;

        foreach (Tenant::all() as $tenant) {
            $parents = Category::query()
                ->where('tenant_id', $tenant->id)
                ->whereNull('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            foreach ($parents as $parent) {
                if (Category::where('parent_id', $parent->id)->exists()) {
                    continue;
                }

                $sort = 1;
                foreach ($this->subcategoryTemplates as $template) {
                    $name = $parent->name.' — '.$template['label'];
                    $baseSlug = Str::slug($parent->slug.'-'.$template['suffix']);

                    Category::create([
                        'tenant_id' => $tenant->id,
                        'parent_id' => $parent->id,
                        'name' => $name,
                        'slug' => Category::uniqueSlug($tenant->id, $baseSlug),
                        'sort_order' => $sort++,
                        'is_visible' => true,
                    ]);

                    $created++;
                }
            }
        }

        $this->command?->info("Created {$created} subcategories for parent categories.");
    }
}

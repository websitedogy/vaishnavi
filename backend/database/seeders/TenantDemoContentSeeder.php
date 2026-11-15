<?php

namespace Database\Seeders;

use App\Models\ArticleComment;
use App\Models\Category;
use App\Models\NewsPost;
use App\Models\Tenant;
use App\Models\TenantTrafficStat;
use Illuminate\Database\Seeder;

class TenantDemoContentSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Tenant::all() as $tenant) {
            if (Category::where('tenant_id', $tenant->id)->exists()) {
                continue;
            }

            $cat = Category::create([
                'tenant_id' => $tenant->id,
                'name' => 'Local News',
                'slug' => 'local-news',
                'sort_order' => 1,
                'is_visible' => true,
            ]);

            $subSort = 1;
            foreach (
                [
                    ['name' => 'City News', 'slug' => 'city-news'],
                    ['name' => 'Rural News', 'slug' => 'rural-news'],
                    ['name' => 'District News', 'slug' => 'district-news'],
                ] as $sub
            ) {
                Category::create([
                    'tenant_id' => $tenant->id,
                    'parent_id' => $cat->id,
                    'name' => $sub['name'],
                    'slug' => Category::uniqueSlug($tenant->id, $sub['slug']),
                    'sort_order' => $subSort++,
                    'is_visible' => true,
                ]);
            }

            $post = NewsPost::where('tenant_id', $tenant->id)->first();
            if ($post) {
                $post->update([
                    'category_id' => $cat->id,
                    'slug' => NewsPost::uniqueSlug($tenant->id, $post->title, $post->id),
                    'content' => '<p>Sample article body for dashboard demo.</p>',
                    'is_featured' => true,
                ]);

                ArticleComment::create([
                    'tenant_id' => $tenant->id,
                    'news_post_id' => $post->id,
                    'author_name' => 'Reader',
                    'author_email' => 'reader@example.com',
                    'body' => 'Great coverage — waiting for more updates!',
                    'is_approved' => false,
                ]);
            }

            TenantTrafficStat::updateOrCreate(
                ['tenant_id' => $tenant->id, 'date' => now()->toDateString()],
                ['visitors' => rand(120, 400), 'page_views' => rand(400, 1200)]
            );
        }
    }
}

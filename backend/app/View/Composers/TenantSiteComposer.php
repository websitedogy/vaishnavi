<?php

namespace App\View\Composers;

use App\Models\Category;
use App\Models\NewsPost;
use App\Models\Tenant;
use App\Support\SocialShareUrls;
use Illuminate\View\View;

class TenantSiteComposer
{
    public function compose(View $view): void
    {
        $tenant = $view->getData()['tenant'] ?? null;
        if (! $tenant) {
            return;
        }

        if (! array_key_exists('navCategories', $view->getData())) {
            $view->with('navCategories', Category::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_visible', true)
                ->whereNull('parent_id')
                ->with(['children' => function ($q) {
                    $q->where('is_visible', true)
                        ->orderBy('sort_order')
                        ->orderBy('name');
                }])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get());
        }

        if (! array_key_exists('shareUrl', $view->getData())) {
            /** @var Tenant $tenant */
            $post = $view->getData()['post'] ?? null;
            $shareUrl = $post instanceof NewsPost
                ? $post->siteUrl($tenant)
                : url()->current();
            $shareTitle = $post instanceof NewsPost
                ? $post->displayTitle().' — '.$tenant->name
                : $tenant->name;

            $view->with([
                'shareUrl' => $shareUrl,
                'shareTitle' => $shareTitle,
                'socialShare' => SocialShareUrls::build($shareUrl, $shareTitle),
            ]);
        }
    }
}

@php
    $seoSlug = old('slug', $article->slug ?? '');
    $siteHost = parse_url(config('app.url'), PHP_URL_HOST) ?: 'yoursite.com';
@endphp

<div class="ta-rankmath" id="rankMathSeo">
    <div class="ta-rankmath__header">
        <h3 class="ta-rankmath__title">Rank Math SEO</h3>
        <button type="button" class="ta-rankmath__collapse" id="rankMathCollapse" aria-expanded="true" title="Toggle panel">▲</button>
    </div>

    <div class="ta-rankmath__body" id="rankMathBody">
        <nav class="ta-rankmath__tabs" role="tablist">
            <button type="button" class="ta-rankmath__tab is-active" data-rm-tab="general" role="tab">General</button>
            <button type="button" class="ta-rankmath__tab" data-rm-tab="advanced" role="tab">Advanced</button>
            <button type="button" class="ta-rankmath__tab" data-rm-tab="schema" role="tab">Schema</button>
            <button type="button" class="ta-rankmath__tab" data-rm-tab="social" role="tab">Social</button>
        </nav>

        {{-- General --}}
        <div class="ta-rankmath__panel is-active" id="rmPanelGeneral" data-rm-panel="general">
            <div class="ta-rankmath__preview">
                <p class="ta-rankmath__label">Preview</p>
                <div class="ta-rankmath__snippet">
                    <p class="ta-rankmath__snippet-title" id="rmPreviewTitle">—</p>
                    <p class="ta-rankmath__snippet-url" id="rmPreviewUrl">{{ $siteHost }}/<span id="rmPreviewSlug">{{ $seoSlug ?: 'sample-post' }}</span></p>
                    <p class="ta-rankmath__snippet-desc" id="rmPreviewDesc">Add a meta description for search results.</p>
                </div>
                <button type="button" class="ta-rankmath__btn-edit" id="rmEditSnippet">Edit Snippet</button>
            </div>

            <div class="ta-rankmath__field">
                <label for="focus_keyword">Focus Keyword <span class="ta-rankmath__help" title="Main keyword for this post">?</span></label>
                <div class="ta-rankmath__keyword-wrap">
                    <input type="text" name="focus_keyword" id="focus_keyword" maxlength="100"
                           value="{{ old('focus_keyword', $article->focus_keyword) }}"
                           placeholder="Example: Rank Math SEO" class="ta-rankmath__input">
                    <span class="ta-rankmath__counter" id="rmKwCount">0 / 100</span>
                </div>
            </div>

            <label class="ta-rankmath__checkbox">
                <input type="checkbox" name="is_pillar_content" value="1" id="is_pillar_content"
                       @checked(old('is_pillar_content', $article->is_pillar_content ?? false))>
                This post is Pillar Content
                <span class="ta-rankmath__help" title="Mark cornerstone content">?</span>
            </label>

            <div class="ta-rankmath__accordion">
                <button type="button" class="ta-rankmath__accordion-head" id="rmBasicSeoToggle" aria-expanded="true">
                    <span>Basic SEO</span>
                    <span class="ta-rankmath__badge ta-rankmath__badge--error" id="rmBasicBadge">× 0 Errors</span>
                    <span class="ta-rankmath__chevron">▼</span>
                </button>
                <ul class="ta-rankmath__checks" id="rmBasicChecks"></ul>
            </div>
        </div>

        {{-- Advanced --}}
        <div class="ta-rankmath__panel" id="rmPanelAdvanced" data-rm-panel="advanced" hidden>
            <div class="ta-rankmath__field">
                <label for="post_slug">URL Slug</label>
                <div class="ta-rankmath__slug-row">
                    <span class="ta-rankmath__slug-prefix">{{ $siteHost }}/</span>
                    <input type="text" name="slug" id="post_slug" class="ta-rankmath__input"
                           value="{{ $seoSlug }}" placeholder="post-url-slug">
                </div>
                <p class="ta-rankmath__hint">Used in the post URL. Auto-generated from title if empty on first save.</p>
            </div>
            <div class="ta-rankmath__field">
                <label for="canonical_url">Canonical URL</label>
                <input type="url" name="canonical_url" id="canonical_url" class="ta-rankmath__input"
                       value="{{ old('canonical_url', $article->canonical_url) }}" placeholder="https://">
            </div>
            <label class="ta-rankmath__checkbox">
                <input type="checkbox" name="seo_noindex" value="1" id="seo_noindex"
                       @checked(old('seo_noindex', $article->seo_noindex ?? false))>
                Use <code>noindex</code> for this post (hide from search engines)
            </label>
        </div>

        {{-- Schema --}}
        <div class="ta-rankmath__panel" id="rmPanelSchema" data-rm-panel="schema" hidden>
            <div class="ta-rankmath__field">
                <label for="schema_type">Schema type</label>
                <select name="schema_type" id="schema_type" class="ta-rankmath__input">
                    @foreach (['article' => 'Article', 'newsarticle' => 'NewsArticle', 'blogposting' => 'BlogPosting'] as $val => $label)
                        <option value="{{ $val }}" @selected(old('schema_type', $article->schema_type ?? 'article') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="ta-rankmath__hint">Structured data type for Google rich results.</p>
            </div>
        </div>

        {{-- Social --}}
        <div class="ta-rankmath__panel" id="rmPanelSocial" data-rm-panel="social" hidden>
            <div class="ta-rankmath__field">
                <label for="og_title">Social title</label>
                <input type="text" name="og_title" id="og_title" class="ta-rankmath__input" maxlength="255"
                       value="{{ old('og_title', $article->og_title) }}"
                       placeholder="Leave empty to use SEO title">
            </div>
            <div class="ta-rankmath__field">
                <label for="og_description">Social description</label>
                <textarea name="og_description" id="og_description" rows="3" class="ta-rankmath__input" maxlength="500"
                          placeholder="Leave empty to use meta description">{{ old('og_description', $article->og_description) }}</textarea>
            </div>
            <div class="ta-rankmath__field">
                <label for="og_image">Social image URL</label>
                <input type="text" name="og_image" id="og_image" class="ta-rankmath__input"
                       value="{{ old('og_image', $article->og_image) }}"
                       placeholder="Featured image used if empty">
            </div>
        </div>
    </div>
</div>

{{-- Hidden SEO fields (snippet modal syncs here) --}}
<input type="hidden" name="meta_title" id="meta_title" value="{{ old('meta_title', $article->meta_title) }}">
<input type="hidden" name="meta_description" id="meta_description" value="{{ old('meta_description', $article->meta_description) }}">

<div class="ta-rankmath-modal" id="rmSnippetModal" aria-hidden="true">
    <div class="ta-rankmath-modal__backdrop" id="rmSnippetBackdrop"></div>
    <div class="ta-rankmath-modal__panel" role="dialog">
        <div class="ta-rankmath-modal__head">
            <strong>Edit Snippet</strong>
            <button type="button" class="ta-rankmath-modal__close" id="rmSnippetClose">&times;</button>
        </div>
        <div class="ta-rankmath-modal__body">
            <div class="ta-rankmath__field">
                <label for="rm_modal_title">SEO Title</label>
                <input type="text" id="rm_modal_title" class="ta-rankmath__input" maxlength="60" placeholder="Post title for Google">
                <span class="ta-rankmath__hint" id="rmModalTitleLen">0 / 60 recommended</span>
            </div>
            <div class="ta-rankmath__field">
                <label for="rm_modal_slug">Slug</label>
                <input type="text" id="rm_modal_slug" class="ta-rankmath__input" placeholder="url-slug">
            </div>
            <div class="ta-rankmath__field">
                <label for="rm_modal_desc">Meta Description</label>
                <textarea id="rm_modal_desc" rows="4" class="ta-rankmath__input" maxlength="160" placeholder="120–160 characters ideal"></textarea>
                <span class="ta-rankmath__hint" id="rmModalDescLen">0 / 160 recommended</span>
            </div>
        </div>
        <div class="ta-rankmath-modal__foot">
            <button type="button" class="ta-rankmath__btn-primary" id="rmSnippetSave">Update snippet</button>
        </div>
    </div>
</div>

/**
 * Rank Math SEO — live analysis for post editor
 */
(function () {
    const cfg = window.taRankMath || {};
    const postTitle = document.getElementById('postTitle');
    const metaTitle = document.getElementById('meta_title');
    const metaDesc = document.getElementById('meta_description');
    const focusKw = document.getElementById('focus_keyword');
    const postSlug = document.getElementById('post_slug');
    const siteHost = cfg.host || window.location.host;

    function slugify(text) {
        return (text || '')
            .toLowerCase()
            .trim()
            .replace(/[^\w\s-]/g, '')
            .replace(/[\s_-]+/g, '-')
            .replace(/^-+|-+$/g, '') || 'post';
    }

    function getContentText() {
        if (typeof tinymce !== 'undefined') {
            const ed = tinymce.get('content');
            if (ed) return ed.getContent({ format: 'text' }).trim();
        }
        const ta = document.getElementById('content');
        return ta ? ta.value.replace(/<[^>]+>/g, ' ').trim() : '';
    }

    function wordCount(text) {
        return text ? text.split(/\s+/).filter(Boolean).length : 0;
    }

    function kwInText(kw, text) {
        if (!kw) return true;
        return text.toLowerCase().includes(kw.toLowerCase());
    }

    function contentStartsWithKw(kw, text) {
        if (!kw) return true;
        const start = text.toLowerCase().slice(0, Math.max(200, kw.length * 3));
        return start.includes(kw.toLowerCase());
    }

    function getSeoTitle() {
        return (metaTitle?.value || postTitle?.value || '').trim();
    }

    function getSlug() {
        return slugify(postSlug?.value || postTitle?.value || 'sample-post');
    }

    function runAnalysis() {
        const kw = (focusKw?.value || '').trim();
        const seoTitle = getSeoTitle();
        const desc = (metaDesc?.value || '').trim();
        const slug = getSlug();
        const content = getContentText();
        const words = wordCount(content);

        const elTitle = document.getElementById('rmPreviewTitle');
        const elSlug = document.getElementById('rmPreviewSlug');
        const elDesc = document.getElementById('rmPreviewDesc');
        const elUrl = document.getElementById('rmPreviewUrl');
        const kwCount = document.getElementById('rmKwCount');

        if (elTitle) elTitle.textContent = seoTitle || '—';
        if (elSlug) elSlug.textContent = slug;
        if (elUrl) elUrl.innerHTML = siteHost + '/<span id="rmPreviewSlug">' + slug + '</span>';
        if (elDesc) elDesc.textContent = desc || 'Add a meta description for search results.';
        if (kwCount) {
            kwCount.textContent = kw.length + ' / 100';
            kwCount.classList.toggle('ta-rankmath__counter--warn', kw.length === 0);
        }

        const hasKw = kw.length > 0;
        const kwSlug = kw.toLowerCase().replace(/\s+/g, '-');

        const checks = [
            {
                label: 'Add Focus Keyword to the SEO title.',
                pass: hasKw && kwInText(kw, seoTitle),
            },
            {
                label: 'Add Focus Keyword to your SEO Meta Description.',
                pass: hasKw && kwInText(kw, desc),
            },
            {
                label: 'Use Focus Keyword in the URL.',
                pass: hasKw && (slug.includes(kwSlug) || kwInText(kw, slug.replace(/-/g, ' '))),
            },
            {
                label: 'Use Focus Keyword at the beginning of your content.',
                pass: hasKw && contentStartsWithKw(kw, content),
            },
            {
                label: 'Use Focus Keyword in the content.',
                pass: hasKw && kwInText(kw, content),
            },
            {
                label: 'Content should be <strong>600–2500</strong> words long.',
                pass: words >= 600 && words <= 2500,
                extra: words + ' words',
            },
        ];

        const list = document.getElementById('rmBasicChecks');
        const badge = document.getElementById('rmBasicBadge');
        if (!list) return;

        let errors = 0;
        list.innerHTML = '';
        checks.forEach((c) => {
            if (!c.pass) errors++;
            const li = document.createElement('li');
            li.className = c.pass ? 'is-pass' : 'is-fail';
            li.innerHTML =
                '<span class="ta-rankmath__check-icon">' + (c.pass ? '✓' : '×') + '</span>' +
                '<span class="ta-rankmath__check-text">' + c.label + (c.extra ? ' <em>(' + c.extra + ')</em>' : '') + '</span>' +
                '<span class="ta-rankmath__help" title="SEO tip">?</span>';
            list.appendChild(li);
        });

        if (badge) {
            if (errors === 0) {
                badge.textContent = '✓ Good';
                badge.className = 'ta-rankmath__badge ta-rankmath__badge--ok';
            } else {
                badge.textContent = '× ' + errors + ' Error' + (errors > 1 ? 's' : '');
                badge.className = 'ta-rankmath__badge ta-rankmath__badge--error';
            }
        }
    }

    function bindInputs() {
        [postTitle, metaTitle, metaDesc, focusKw, postSlug].forEach((el) => {
            el?.addEventListener('input', runAnalysis);
            el?.addEventListener('change', runAnalysis);
        });
        postTitle?.addEventListener('input', () => {
            if (postSlug && !postSlug.dataset.userEdited) {
                postSlug.value = slugify(postTitle.value);
            }
        });
        postSlug?.addEventListener('input', () => {
            postSlug.dataset.userEdited = '1';
        });
    }

    /* Tabs */
    document.querySelectorAll('[data-rm-tab]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const tab = btn.dataset.rmTab;
            document.querySelectorAll('[data-rm-tab]').forEach((b) => b.classList.toggle('is-active', b === btn));
            document.querySelectorAll('[data-rm-panel]').forEach((p) => {
                const show = p.dataset.rmPanel === tab;
                p.hidden = !show;
                p.classList.toggle('is-active', show);
            });
        });
    });

    /* Collapse */
    document.getElementById('rankMathCollapse')?.addEventListener('click', () => {
        const body = document.getElementById('rankMathBody');
        const btn = document.getElementById('rankMathCollapse');
        const open = body.classList.toggle('is-collapsed');
        btn.textContent = open ? '▼' : '▲';
        btn.setAttribute('aria-expanded', !open);
    });

    /* Basic SEO accordion */
    document.getElementById('rmBasicSeoToggle')?.addEventListener('click', function () {
        const list = document.getElementById('rmBasicChecks');
        const open = list.classList.toggle('is-collapsed');
        this.setAttribute('aria-expanded', !open);
    });

    /* Edit snippet modal */
    const modal = document.getElementById('rmSnippetModal');
    const modalTitle = document.getElementById('rm_modal_title');
    const modalSlug = document.getElementById('rm_modal_slug');
    const modalDesc = document.getElementById('rm_modal_desc');

    function openSnippetModal() {
        modalTitle.value = metaTitle?.value || postTitle?.value || '';
        modalSlug.value = postSlug?.value || slugify(postTitle?.value);
        modalDesc.value = metaDesc?.value || '';
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        updateModalCounters();
    }

    function closeSnippetModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
    }

    function updateModalCounters() {
        const t = document.getElementById('rmModalTitleLen');
        const d = document.getElementById('rmModalDescLen');
        if (t) t.textContent = (modalTitle?.value.length || 0) + ' / 60 recommended';
        if (d) d.textContent = (modalDesc?.value.length || 0) + ' / 160 recommended';
    }

    document.getElementById('rmEditSnippet')?.addEventListener('click', openSnippetModal);
    document.getElementById('rmSnippetClose')?.addEventListener('click', closeSnippetModal);
    document.getElementById('rmSnippetBackdrop')?.addEventListener('click', closeSnippetModal);
    [modalTitle, modalDesc].forEach((el) => el?.addEventListener('input', updateModalCounters));

    document.getElementById('rmSnippetSave')?.addEventListener('click', () => {
        if (metaTitle) metaTitle.value = modalTitle.value.trim();
        if (metaDesc) metaDesc.value = modalDesc.value.trim();
        if (postSlug) {
            postSlug.value = slugify(modalSlug.value);
            postSlug.dataset.userEdited = '1';
        }
        closeSnippetModal();
        runAnalysis();
    });

    bindInputs();
    runAnalysis();

    window.taRankMathAnalyze = runAnalysis;
    document.addEventListener('ta-content-change', runAnalysis);
    setInterval(runAnalysis, 4000);

    /* Hook TinyMCE when ready */
    const waitTiny = setInterval(() => {
        if (typeof tinymce === 'undefined' || !tinymce.get('content')) return;
        clearInterval(waitTiny);
        tinymce.get('content').on('keyup change undo redo SetContent', () => {
            document.dispatchEvent(new Event('ta-content-change'));
        });
    }, 500);
})();

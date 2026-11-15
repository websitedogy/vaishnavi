<footer class="lp-footer">
    <div class="lp-container">
        <div class="lp-footer__grid">
            <div>
                <p class="lp-footer__brand">Doggy SaaS News</p>
                <p style="margin: 0; font-size: 0.875rem; max-width: 20rem;">
                    Multi-tenant news publishing for regional media. Built for the open market.
                </p>
            </div>
            <div>
                <p style="color: #fff; font-weight: 600; margin: 0 0 0.75rem; font-size: 0.875rem;">Product</p>
                <ul class="lp-footer__links">
                    <li><a href="#features">Features</a></li>
                    <li><a href="#register">Register</a></li>
                    <li><a href="#faq">FAQ</a></li>
                </ul>
            </div>
            <div>
                <p style="color: #fff; font-weight: 600; margin: 0 0 0.75rem; font-size: 0.875rem;">Access</p>
                <ul class="lp-footer__links">
                    <li><a href="{{ route('tenant-admin.login') }}">Tenant admin login</a></li>
                    <li><a href="{{ route('super-admin.login') }}">Platform admin</a></li>
                </ul>
            </div>
        </div>
        <div class="lp-footer__bottom">
            © {{ date('Y') }} Doggy SaaS News. All rights reserved.
        </div>
    </div>
</footer>

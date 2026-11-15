<section class="lp-register" id="register">
    <div class="lp-container">
        <div class="lp-section-head">
            <h2>Start your free trial</h2>
            <p>Register your news company. You will sign in to the tenant admin panel with the email and password you set here.</p>
        </div>

        <div class="lp-register__box">
            @if ($errors->any())
                <div class="lp-alert lp-alert--error">
                    Please fix the errors below and try again.
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" novalidate>
                @csrf

                <div class="lp-form__grid lp-form__grid--2" style="margin-bottom: 1rem;">
                    <div class="lp-field" style="grid-column: 1 / -1;">
                        <label for="company_name">News site / company name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required
                               placeholder="e.g. Telugu Daily News">
                        @error('company_name')<p class="lp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="lp-field">
                        <label for="company_email">Company contact email <span style="color:#dc2626">*</span></label>
                        <input type="email" name="company_email" id="company_email" value="{{ old('company_email') }}" required
                               placeholder="contact@yournews.com">
                        @error('company_email')<p class="lp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="lp-field">
                        <label for="subscription">Plan (trial)</label>
                        <select name="subscription" id="subscription">
                            @foreach ($plans as $key => $plan)
                                <option value="{{ $key }}" @selected(old('subscription', 'basic') === $key)>
                                    {{ $plan['label'] }} — ${{ $plan['price'] }}/mo after trial
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <p style="font-weight: 600; font-size: 0.875rem; margin: 0 0 1rem; color: #334155;">Tenant admin login credentials</p>

                <div class="lp-form__grid lp-form__grid--2" style="margin-bottom: 1rem;">
                    <div class="lp-field" style="grid-column: 1 / -1;">
                        <label for="admin_name">Your full name <span style="color:#dc2626">*</span></label>
                        <input type="text" name="admin_name" id="admin_name" value="{{ old('admin_name') }}" required
                               placeholder="Site administrator name">
                        @error('admin_name')<p class="lp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="lp-field" style="grid-column: 1 / -1;">
                        <label for="admin_email">Login email <span style="color:#dc2626">*</span></label>
                        <input type="email" name="admin_email" id="admin_email" value="{{ old('admin_email') }}" required
                               placeholder="you@yournews.com" autocomplete="email">
                        @error('admin_email')<p class="lp-field__error">{{ $message }}</p>@enderror
                        <p class="lp-form__note">Use this email at <a href="{{ route('tenant-admin.login') }}">tenant admin login</a>.</p>
                    </div>

                    <div class="lp-field">
                        <label for="admin_password">Password <span style="color:#dc2626">*</span></label>
                        <input type="password" name="admin_password" id="admin_password" required
                               autocomplete="new-password" placeholder="Min. 8 characters">
                        @error('admin_password')<p class="lp-field__error">{{ $message }}</p>@enderror
                    </div>

                    <div class="lp-field">
                        <label for="admin_password_confirmation">Confirm password <span style="color:#dc2626">*</span></label>
                        <input type="password" name="admin_password_confirmation" id="admin_password_confirmation" required
                               autocomplete="new-password">
                    </div>
                </div>

                <label class="lp-form__terms">
                    <input type="checkbox" name="terms" value="1" @checked(old('terms')) required>
                    <span>I agree to the <a href="#faq">terms of service</a> and privacy policy for Doggy SaaS News.</span>
                </label>
                @error('terms')<p class="lp-field__error">{{ $message }}</p>@enderror

                <button type="submit" class="lp-btn lp-btn--primary lp-btn--lg lp-btn--block" style="margin-top: 1.25rem;">
                    Register & go to login
                </button>
            </form>
        </div>
    </div>
</section>

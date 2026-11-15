@extends('layouts.tenant-admin')
@section('page-title','Social Media')
@section('content')
<form method="POST" action="{{ route('tenant-admin.social.update') }}" class="max-w-2xl">@csrf @method('PUT')
<div class="bg-white rounded-xl border p-6 space-y-4">
<h3 class="font-semibold">Auto posting</h3>
<label class="flex gap-2 text-sm"><input type="checkbox" name="facebook_auto_post" value="1" @checked($settings['facebook_auto_post']??false)> Auto Facebook post</label>
<input name="facebook_page_id" value="{{ $settings['facebook_page_id'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Facebook page ID">
<label class="flex gap-2 text-sm"><input type="checkbox" name="twitter_auto_post" value="1" @checked($settings['twitter_auto_post']??false)> Auto Twitter/X post</label>
<input name="twitter_api_key" value="{{ $settings['twitter_api_key'] }}" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Twitter API key">
<label class="flex gap-2 text-sm"><input type="checkbox" name="telegram_auto_post" value="1" @checked($settings['telegram_auto_post']??false)> Auto Telegram post</label>
<input name="telegram_channel" value="{{ $settings['telegram_channel'] }}" class="w-full rounded-lg border-slate-300 text-sm">
<label class="flex gap-2 text-sm"><input type="checkbox" name="instagram_enabled" value="1" @checked($settings['instagram_enabled']??false)> Instagram sharing</label>
<label class="flex gap-2 text-sm"><input type="checkbox" name="share_buttons" value="1" @checked($settings['share_buttons']??true)> Social share buttons</label>
<h3 class="font-semibold pt-2">Social login</h3>
<label class="flex gap-2 text-sm"><input type="checkbox" name="social_login_google" value="1" @checked($settings['social_login_google']??false)> Google login</label>
<label class="flex gap-2 text-sm"><input type="checkbox" name="social_login_facebook" value="1" @checked($settings['social_login_facebook']??false)> Facebook login</label>
<button class="mt-4 px-6 py-2.5 bg-brand-600 text-white font-semibold rounded-lg">Save</button>
</div>
</form>
@endsection

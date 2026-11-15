<?php

namespace App\Modules\SuperAdmin\Controllers;

use App\Http\Controllers\Controller;
use App\Shared\Traits\ApiResponseTrait;
use App\Shared\Helpers\AuditHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PlatformSettingsController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $rows = DB::table('platform_settings')->get();
        $settings = [];

        foreach ($rows as $row) {
            $settings[$row->key] = $row->value;
        }

        // Defaults if table is empty
        $defaults = [
            'platform_name' => 'DOGY ePAPER SaaS',
            'platform_tagline' => 'Enterprise White-Label Digital Newspaper Platform',
            'default_currency' => 'INR',
            'default_timezone' => 'Asia/Kolkata',
            'default_language' => 'en',
            'date_format' => 'd M Y',
            'support_email' => 'support@dogyepaper.com',
            'support_phone' => '+91 98765 43210',
            'aws_region' => 'ap-south-1',
            'aws_s3_bucket' => 'dogy-epaper-storage-production',
            'cloudfront_domain' => 'cdn.dogyepaper.com',
            'maintenance_mode' => 'false',
            'enable_self_registration' => 'false',
            'max_pdf_upload_size_mb' => '500',
            'razorpay_key_id' => 'rzp_live_masked_key',
            'stripe_publishable_key' => 'pk_live_masked_key',
            'terms_and_conditions' => 'Standard enterprise terms and conditions for Dogy ePaper SaaS publisher tenants.',
            'privacy_policy' => 'Dogy ePaper SaaS platform privacy and data protection policy.',
            'refund_policy' => 'Prorated refund policy for enterprise subscribers within 14 days of billing.',
        ];

        $merged = array_merge($defaults, $settings);

        return $this->successResponse($merged);
    }

    public function update(Request $request)
    {
        $data = $request->except(['_token']);
        $now = Carbon::now();
        $admin = $request->user();

        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }

            DB::table('platform_settings')->updateOrInsert(
                ['key' => $key],
                ['value' => (string) $value, 'updated_at' => $now]
            );
        }

        AuditHelper::log($admin ? $admin->id : null, 'PLATFORM_SETTINGS_UPDATED', 'PlatformSettings', 1, 'Platform settings modified.');

        return $this->successResponse($data, 'Platform settings saved successfully.');
    }
}

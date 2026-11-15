import React, { useState } from 'react';
import { Modal } from '../../../shared/components/Modal';
import { SubscriptionPackage, Tenant } from '../types';
import { Check, ChevronRight, ChevronLeft, Loader2, Building, Shield, Globe, CreditCard, Mail, Palette, Sparkles } from 'lucide-react';

interface TenantWizardModalProps {
  isOpen: boolean;
  onClose: () => void;
  packages: SubscriptionPackage[];
  onSubmit: (formData: any) => Promise<Tenant>;
}

export const TenantWizardModal: React.FC<TenantWizardModalProps> = ({
  isOpen,
  onClose,
  packages,
  onSubmit,
}) => {
  const [currentStep, setCurrentStep] = useState(1);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const [formData, setFormData] = useState({
    // Step 1: Publisher Information
    name: '',
    company_name: '',
    contact_person: '',
    email: '',
    phone: '',
    address: '',
    state: 'Telangana',
    district: 'Hyderabad',
    mandal: '',

    // Step 2: Tenant Configuration
    slug: '',
    default_language: 'te',
    timezone: 'Asia/Kolkata',
    status: 'active',

    // Step 3: Domain Configuration
    primary_domain: '',

    // Step 4: Subscription
    package_id: packages.length > 0 ? packages[0].id : 1,
    billing_cycle: 'monthly',

    // Step 5: Admin Invitation
    admin_name: '',
    admin_email: '',
    admin_phone: '',

    // Step 6: Branding
    primary_color: '#2563EB',
    secondary_color: '#0EA5E9',
  });

  const updateField = (field: string, val: any) => {
    setFormData((prev) => {
      const next = { ...prev, [field]: val };
      if (field === 'name' && !prev.slug) {
        next.slug = val.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
      }
      if (field === 'name' && !prev.company_name) {
        next.company_name = val + ' Media Pvt Ltd';
      }
      if (field === 'contact_person' && !prev.admin_name) {
        next.admin_name = val;
      }
      if (field === 'email' && !prev.admin_email) {
        next.admin_email = val;
      }
      if (field === 'phone' && !prev.admin_phone) {
        next.admin_phone = val;
      }
      return next;
    });
  };

  const steps = [
    { num: 1, title: 'Publisher Info', icon: Building },
    { num: 2, title: 'Tenant Config', icon: Shield },
    { num: 3, title: 'Domain & DNS', icon: Globe },
    { num: 4, title: 'Subscription', icon: CreditCard },
    { num: 5, title: 'Admin Invite', icon: Mail },
    { num: 6, title: 'Branding', icon: Palette },
    { num: 7, title: 'Review & Create', icon: Sparkles },
  ];

  const handleNext = () => {
    if (currentStep === 1) {
      if (!formData.name || !formData.contact_person || !formData.email) {
        setError('Please fill in all required publisher fields: Newspaper Name, Contact Person, and Email.');
        return;
      }
    }
    setError(null);
    setCurrentStep((prev) => Math.min(prev + 1, 7));
  };

  const handlePrev = () => {
    setError(null);
    setCurrentStep((prev) => Math.max(prev - 1, 1));
  };

  const handleSubmit = async () => {
    setError(null);
    setIsSubmitting(true);
    try {
      await onSubmit(formData);
      onClose();
      setCurrentStep(1);
    } catch (err: any) {
      setError(err?.response?.data?.message || err?.message || 'Tenant registration failed');
    } finally {
      setIsSubmitting(false);
    }
  };

  const selectedPackage = packages.find((p) => p.id === Number(formData.package_id)) || packages[0];

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Create Publisher Tenant"
      description="Multi-Step Enterprise Onboarding Wizard"
      maxWidth="4xl"
    >
      <div className="space-y-6">
        {/* Step Indicator Bar */}
        <div className="flex items-center justify-between border-b border-slate-200 pb-4 overflow-x-auto">
          {steps.map((s, idx) => {
            const Icon = s.icon;
            const isDone = currentStep > s.num;
            const isCurr = currentStep === s.num;

            return (
              <div key={s.num} className="flex items-center flex-shrink-0">
                <div
                  className={`flex items-center space-x-2 px-3 py-1.5 rounded-lg text-xs font-semibold ${
                    isCurr
                      ? 'bg-blue-600 text-white shadow-xs'
                      : isDone
                      ? 'text-emerald-700 bg-emerald-50'
                      : 'text-slate-400 bg-slate-50'
                  }`}
                >
                  {isDone ? <Check className="w-4 h-4" /> : <Icon className="w-4 h-4" />}
                  <span>{s.title}</span>
                </div>
                {idx < steps.length - 1 && <ChevronRight className="w-4 h-4 text-slate-300 mx-1 flex-shrink-0" />}
              </div>
            );
          })}
        </div>

        {error && (
          <div className="text-xs text-rose-700 bg-rose-50 border border-rose-200 rounded-xl p-3">
            {error}
          </div>
        )}

        {/* Step 1: Publisher Information */}
        {currentStep === 1 && (
          <div className="space-y-4">
            <h4 className="text-sm font-bold text-slate-900">Step 1: Newspaper Publisher Information</h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <div>
                <label className="block font-medium text-slate-700 mb-1">Newspaper Name *</label>
                <input
                  type="text"
                  value={formData.name}
                  onChange={(e) => updateField('name', e.target.value)}
                  placeholder="e.g. Telangana Samacharam"
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Company / Publisher Entity Name</label>
                <input
                  type="text"
                  value={formData.company_name}
                  onChange={(e) => updateField('company_name', e.target.value)}
                  placeholder="e.g. Samacharam Media Pvt Ltd"
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Contact Person *</label>
                <input
                  type="text"
                  value={formData.contact_person}
                  onChange={(e) => updateField('contact_person', e.target.value)}
                  placeholder="e.g. K. R. Rao (Managing Editor)"
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Publisher Email *</label>
                <input
                  type="email"
                  value={formData.email}
                  onChange={(e) => updateField('email', e.target.value)}
                  placeholder="publisher@samacharam.com"
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Contact Mobile Number</label>
                <input
                  type="text"
                  value={formData.phone}
                  onChange={(e) => updateField('phone', e.target.value)}
                  placeholder="+91 98480 12345"
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">State</label>
                <input
                  type="text"
                  value={formData.state}
                  onChange={(e) => updateField('state', e.target.value)}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">District</label>
                <input
                  type="text"
                  value={formData.district}
                  onChange={(e) => updateField('district', e.target.value)}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Mandal / Town</label>
                <input
                  type="text"
                  value={formData.mandal}
                  onChange={(e) => updateField('mandal', e.target.value)}
                  placeholder="Khairatabad"
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
          </div>
        )}

        {/* Step 2: Tenant Configuration */}
        {currentStep === 2 && (
          <div className="space-y-4">
            <h4 className="text-sm font-bold text-slate-900">Step 2: Platform Tenant Architecture & Storage</h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <div>
                <label className="block font-medium text-slate-700 mb-1">Unique Tenant Slug *</label>
                <input
                  type="text"
                  value={formData.slug}
                  onChange={(e) => updateField('slug', e.target.value)}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 font-mono focus:ring-2 focus:ring-blue-500"
                />
                <span className="text-[10px] text-slate-500">Auto-determines database identifier and S3 bucket prefix.</span>
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Initial Status</label>
                <select
                  value={formData.status}
                  onChange={(e) => updateField('status', e.target.value)}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white"
                >
                  <option value="active">Active (Full production)</option>
                  <option value="trial">14-Day Free Trial</option>
                  <option value="pending">Pending Approval</option>
                </select>
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Primary Edition Language</label>
                <select
                  value={formData.default_language}
                  onChange={(e) => updateField('default_language', e.target.value)}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 bg-white"
                >
                  <option value="te">Telugu (తెలుగు)</option>
                  <option value="en">English</option>
                  <option value="hi">Hindi (हिन्दी)</option>
                  <option value="ta">Tamil (தமிழ்)</option>
                  <option value="kn">Kannada (ಕನ್ನಡ)</option>
                  <option value="ur">Urdu (اردو)</option>
                </select>
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Timezone</label>
                <input
                  type="text"
                  value={formData.timezone}
                  disabled
                  className="w-full border border-slate-200 bg-slate-50 text-slate-500 rounded-lg px-3 py-2"
                />
              </div>
            </div>
          </div>
        )}

        {/* Step 3: Domain Configuration */}
        {currentStep === 3 && (
          <div className="space-y-4">
            <h4 className="text-sm font-bold text-slate-900">Step 3: Custom Domain & DNS Setup</h4>
            <div className="space-y-3 text-xs">
              <div>
                <label className="block font-medium text-slate-700 mb-1">Primary Custom Domain</label>
                <input
                  type="text"
                  value={formData.primary_domain}
                  onChange={(e) => updateField('primary_domain', e.target.value)}
                  placeholder="e.g. epaper.samacharam.com"
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 font-mono"
                />
              </div>

              <div className="bg-blue-50/70 border border-blue-200 rounded-xl p-4 text-xs text-blue-900 space-y-2">
                <p className="font-semibold">DNS Verification Instructions:</p>
                <p className="text-slate-600">
                  After registration, the publisher will need to add a TXT verification challenge and CNAME record pointing to our AWS CloudFront CDN distribution:
                </p>
                <div className="bg-white p-2.5 rounded-lg border border-blue-200 font-mono text-[11px] text-slate-800 space-y-1">
                  <div>CNAME Record: <span className="font-bold">{formData.primary_domain || 'epaper.example.com'}</span> ➔ <span className="text-blue-600 font-bold">cname.dogyepaper.com</span></div>
                  <div>TXT Challenge: <span className="font-bold">_dogy-challenge.{formData.primary_domain || 'epaper.example.com'}</span> ➔ <span className="text-slate-500">Auto-generated token</span></div>
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Step 4: Subscription */}
        {currentStep === 4 && (
          <div className="space-y-4">
            <h4 className="text-sm font-bold text-slate-900">Step 4: Subscription Package & Billing</h4>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
              {packages.map((pkg) => (
                <div
                  key={pkg.id}
                  onClick={() => updateField('package_id', pkg.id)}
                  className={`border-2 rounded-xl p-4 cursor-pointer transition-all ${
                    formData.package_id === pkg.id
                      ? 'border-blue-600 bg-blue-50/40 shadow-xs'
                      : 'border-slate-200 hover:border-slate-300'
                  }`}
                >
                  <h5 className="font-bold text-sm text-slate-900">{pkg.name}</h5>
                  <p className="text-xs text-slate-500 mt-1">{pkg.description}</p>
                  <p className="text-lg font-black text-slate-900 mt-3">₹{pkg.price.toLocaleString()}<span className="text-xs font-normal text-slate-500"> / {pkg.billing_cycle}</span></p>
                  {pkg.entitlements && (
                    <ul className="mt-3 text-[11px] text-slate-600 space-y-1 border-t border-slate-200 pt-2">
                      <li>• {pkg.entitlements.max_domains} Custom Domain(s)</li>
                      <li>• {pkg.entitlements.storage_limit_gb} GB Storage</li>
                      <li>• {pkg.entitlements.monthly_edition_limit} Editions/Month</li>
                    </ul>
                  )}
                </div>
              ))}
            </div>

            <div className="mt-3 text-xs">
              <label className="block font-medium text-slate-700 mb-1">Billing Frequency</label>
              <select
                value={formData.billing_cycle}
                onChange={(e) => updateField('billing_cycle', e.target.value)}
                className="w-full md:w-64 border border-slate-300 rounded-lg px-3 py-2 bg-white"
              >
                <option value="monthly">Monthly Recurring</option>
                <option value="quarterly">Quarterly</option>
                <option value="half_yearly">Half-Yearly</option>
                <option value="yearly">Yearly (Annual Pre-paid)</option>
              </select>
            </div>
          </div>
        )}

        {/* Step 5: Admin Invitation */}
        {currentStep === 5 && (
          <div className="space-y-4">
            <h4 className="text-sm font-bold text-slate-900">Step 5: Tenant Administrator Secure Invitation</h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <div>
                <label className="block font-medium text-slate-700 mb-1">Tenant Admin Name *</label>
                <input
                  type="text"
                  value={formData.admin_name}
                  onChange={(e) => updateField('admin_name', e.target.value)}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                />
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Tenant Admin Email *</label>
                <input
                  type="email"
                  value={formData.admin_email}
                  onChange={(e) => updateField('admin_email', e.target.value)}
                  className="w-full border border-slate-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500"
                />
              </div>
            </div>
            <p className="text-xs text-slate-500">
              An encrypted one-time invitation link will be generated for the tenant administrator with 7-day validity.
            </p>
          </div>
        )}

        {/* Step 6: Branding */}
        {currentStep === 6 && (
          <div className="space-y-4">
            <h4 className="text-sm font-bold text-slate-900">Step 6: White-Label Branding Setup</h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
              <div>
                <label className="block font-medium text-slate-700 mb-1">Primary Brand Color</label>
                <div className="flex items-center space-x-2">
                  <input
                    type="color"
                    value={formData.primary_color}
                    onChange={(e) => updateField('primary_color', e.target.value)}
                    className="w-10 h-10 border border-slate-300 rounded-lg p-0.5 cursor-pointer"
                  />
                  <input
                    type="text"
                    value={formData.primary_color}
                    onChange={(e) => updateField('primary_color', e.target.value)}
                    className="flex-1 border border-slate-300 rounded-lg px-3 py-2 font-mono uppercase"
                  />
                </div>
              </div>
              <div>
                <label className="block font-medium text-slate-700 mb-1">Secondary Brand Color</label>
                <div className="flex items-center space-x-2">
                  <input
                    type="color"
                    value={formData.secondary_color}
                    onChange={(e) => updateField('secondary_color', e.target.value)}
                    className="w-10 h-10 border border-slate-300 rounded-lg p-0.5 cursor-pointer"
                  />
                  <input
                    type="text"
                    value={formData.secondary_color}
                    onChange={(e) => updateField('secondary_color', e.target.value)}
                    className="flex-1 border border-slate-300 rounded-lg px-3 py-2 font-mono uppercase"
                  />
                </div>
              </div>
            </div>
          </div>
        )}

        {/* Step 7: Review & Create */}
        {currentStep === 7 && (
          <div className="space-y-4">
            <h4 className="text-sm font-bold text-slate-900">Step 7: Configuration Review & Provisioning</h4>
            <div className="bg-slate-50 border border-slate-200 rounded-xl p-4 divide-y divide-slate-200 text-xs">
              <div className="py-2 flex justify-between">
                <span className="text-slate-500 font-medium">Newspaper Name:</span>
                <span className="font-bold text-slate-900">{formData.name}</span>
              </div>
              <div className="py-2 flex justify-between">
                <span className="text-slate-500 font-medium">Unique Tenant Slug:</span>
                <span className="font-mono font-bold text-blue-600">{formData.slug}</span>
              </div>
              <div className="py-2 flex justify-between">
                <span className="text-slate-500 font-medium">Publisher Contact:</span>
                <span className="text-slate-900">{formData.contact_person} ({formData.email})</span>
              </div>
              <div className="py-2 flex justify-between">
                <span className="text-slate-500 font-medium">Primary Domain:</span>
                <span className="font-mono text-slate-900">{formData.primary_domain || 'Pending Setup'}</span>
              </div>
              <div className="py-2 flex justify-between">
                <span className="text-slate-500 font-medium">Subscription Package:</span>
                <span className="font-bold text-slate-900">{selectedPackage?.name} (₹{selectedPackage?.price} / {formData.billing_cycle})</span>
              </div>
              <div className="py-2 flex justify-between">
                <span className="text-slate-500 font-medium">Tenant Administrator:</span>
                <span className="text-slate-900">{formData.admin_name} ({formData.admin_email})</span>
              </div>
            </div>
          </div>
        )}

        {/* Navigation Actions */}
        <div className="flex items-center justify-between pt-4 border-t border-slate-200">
          <button
            type="button"
            onClick={handlePrev}
            disabled={currentStep === 1 || isSubmitting}
            className="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg flex items-center space-x-1.5 disabled:opacity-40"
          >
            <ChevronLeft className="w-4 h-4" />
            <span>Previous</span>
          </button>

          {currentStep < 7 ? (
            <button
              type="button"
              onClick={handleNext}
              className="px-4 py-2 text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center space-x-1.5 shadow-xs"
            >
              <span>Next Step</span>
              <ChevronRight className="w-4 h-4" />
            </button>
          ) : (
            <button
              type="button"
              onClick={handleSubmit}
              disabled={isSubmitting}
              className="px-5 py-2.5 text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg flex items-center space-x-2 shadow-xs"
            >
              {isSubmitting && <Loader2 className="w-4 h-4 animate-spin" />}
              <span>Confirm & Provision Tenant</span>
            </button>
          )}
        </div>
      </div>
    </Modal>
  );
};

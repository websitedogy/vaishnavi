'use client';

import React, { useState } from 'react';
import { Breadcrumb } from '@/modules/public-view/components/Breadcrumb/Breadcrumb';
import { PublicBranding, PublicPublication } from '@/modules/public-view/types/public.types';
import { Mail, Phone, MapPin, Send, CheckCircle2 } from 'lucide-react';

interface ContactPageProps {
  tenantSlug: string;
  publication: PublicPublication;
  branding: PublicBranding;
}

export const ContactPage: React.FC<ContactPageProps> = ({
  tenantSlug,
  publication,
  branding,
}) => {
  const [submitted, setSubmitted] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    subject: 'General Inquiry',
    message: '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    setSubmitted(true);
  };

  const breadcrumbItems = [
    { label: 'Contact Us', active: true },
  ];

  return (
    <div className="space-y-6">
      <Breadcrumb tenantSlug={tenantSlug} items={breadcrumbItems} />

      <div className="mx-auto max-w-4xl px-4 space-y-8">
        {/* Header */}
        <div className="border-b border-slate-200 pb-4">
          <h1 className="text-xl md:text-2xl font-bold text-slate-900 font-masthead">
            Contact Editorial & Circulation Desk
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Get in touch with the editorial team, circulation bureau, or technical support of {branding.newspaper_name}
          </p>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-12 gap-8">
          {/* Contact Details Card */}
          <div className="md:col-span-5 space-y-4">
            <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5">
              <h2 className="text-sm font-bold text-slate-900 uppercase tracking-wider">
                Office Information
              </h2>

              <div className="flex items-start space-x-3 text-xs text-slate-600">
                <MapPin className="h-4 w-4 text-slate-400 shrink-0 mt-0.5" />
                <div>
                  <span className="font-semibold text-slate-800 block">Editorial Office</span>
                  <span>{branding.newspaper_name} Main Press Facility & Bureau</span>
                </div>
              </div>

              <div className="flex items-start space-x-3 text-xs text-slate-600">
                <Mail className="h-4 w-4 text-slate-400 shrink-0 mt-0.5" />
                <div>
                  <span className="font-semibold text-slate-800 block">Email Inquiries</span>
                  <a
                    href="mailto:editorial@epaper.example.com"
                    className="text-blue-600 hover:underline"
                  >
                    editorial@{tenantSlug}.dogyepaper.com
                  </a>
                </div>
              </div>

              <div className="flex items-start space-x-3 text-xs text-slate-600">
                <Phone className="h-4 w-4 text-slate-400 shrink-0 mt-0.5" />
                <div>
                  <span className="font-semibold text-slate-800 block">Newsdesk Support</span>
                  <span>Mon – Sat, 9:00 AM – 6:00 PM IST</span>
                </div>
              </div>
            </div>

            <div className="rounded-xl border border-blue-100 bg-blue-50/50 p-4 text-xs text-blue-900">
              <span className="font-bold block mb-1">Letters to the Editor</span>
              Send your reader feedback, story leads, or corrections directly to the editorial team.
            </div>
          </div>

          {/* Contact Form */}
          <div className="md:col-span-7">
            <div className="rounded-2xl border border-slate-200 bg-white p-6 md:p-8 shadow-sm">
              {submitted ? (
                <div className="py-8 text-center space-y-3">
                  <CheckCircle2 className="mx-auto h-12 w-12 text-emerald-500" />
                  <h3 className="text-base font-bold text-slate-900">Message Received</h3>
                  <p className="text-xs text-slate-500 max-w-sm mx-auto">
                    Thank you for contacting {branding.newspaper_name}. Our editorial desk has received
                    your message and will review it promptly.
                  </p>
                  <button
                    type="button"
                    onClick={() => {
                      setSubmitted(false);
                      setFormData({ name: '', email: '', subject: 'General Inquiry', message: '' });
                    }}
                    className="text-xs font-semibold text-blue-600 hover:underline"
                  >
                    Send another message
                  </button>
                </div>
              ) : (
                <form onSubmit={handleSubmit} className="space-y-4">
                  <h2 className="text-sm font-bold text-slate-900">Send an Inquiry</h2>

                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                      Your Name
                    </label>
                    <input
                      type="text"
                      required
                      value={formData.name}
                      onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                      placeholder="e.g. Ramesh Kumar"
                      className="w-full rounded-lg border border-slate-300 bg-white py-2 px-3 text-xs text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                      Email Address
                    </label>
                    <input
                      type="email"
                      required
                      value={formData.email}
                      onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                      placeholder="ramesh@example.com"
                      className="w-full rounded-lg border border-slate-300 bg-white py-2 px-3 text-xs text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    />
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                      Subject
                    </label>
                    <select
                      value={formData.subject}
                      onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
                      className="w-full rounded-lg border border-slate-300 bg-white py-2 px-3 text-xs text-slate-700 focus:border-blue-600 focus:outline-none"
                    >
                      <option value="General Inquiry">General Inquiry</option>
                      <option value="Letter to Editor">Letter to Editor</option>
                      <option value="Advertising & Classifieds">Advertising & Classifieds</option>
                      <option value="Technical / Reader Issue">Technical / Reader Issue</option>
                      <option value="Circulation / Distribution">Circulation / Distribution</option>
                    </select>
                  </div>

                  <div>
                    <label className="block text-xs font-semibold text-slate-700 mb-1">
                      Message
                    </label>
                    <textarea
                      required
                      rows={4}
                      value={formData.message}
                      onChange={(e) => setFormData({ ...formData, message: e.target.value })}
                      placeholder="Write your message here..."
                      className="w-full rounded-lg border border-slate-300 bg-white py-2 px-3 text-xs text-slate-900 focus:border-blue-600 focus:outline-none focus:ring-1 focus:ring-blue-600"
                    />
                  </div>

                  <button
                    type="submit"
                    className="flex items-center justify-center gap-1.5 w-full rounded-lg py-2.5 text-xs font-semibold text-white shadow transition hover:opacity-95"
                    style={{ backgroundColor: branding.primary_color }}
                  >
                    <Send className="h-3.5 w-3.5" />
                    <span>Submit Inquiry</span>
                  </button>
                </form>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

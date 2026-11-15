import React, { useEffect, useState } from 'react';
import {
  Newspaper,
  Save,
  Globe,
  Mail,
  Phone,
  MapPin,
  Upload,
  AlertCircle,
  CheckCircle2,
  RefreshCw,
  Share2,
} from 'lucide-react';
import { newspaperProfileApi } from '../services/api';
import { NewspaperProfile } from '../types';

export const NewspaperProfileView: React.FC = () => {
  const [profile, setProfile] = useState<Partial<NewspaperProfile>>({});
  const [loading, setLoading] = useState(true);
  const [saving, setSaving] = useState(false);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  const fetchProfile = async () => {
    setLoading(true);
    setErrorMsg(null);
    try {
      const res = await newspaperProfileApi.get();
      setProfile(res.data || {});
    } catch (err: any) {
      setErrorMsg(err?.response?.data?.message || 'Failed to fetch newspaper profile.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchProfile();
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    setSuccessMsg(null);
    setErrorMsg(null);
    try {
      const res = await newspaperProfileApi.update(profile);
      setProfile(res.data);
      setSuccessMsg('Newspaper profile and publication metadata saved successfully.');
      setTimeout(() => setSuccessMsg(null), 4000);
    } catch (err: any) {
      setErrorMsg(err?.response?.data?.message || 'Failed to update newspaper profile.');
    } finally {
      setSaving(false);
    }
  };

  if (loading) {
    return (
      <div className="flex flex-col items-center justify-center min-h-[350px] space-y-3">
        <RefreshCw className="w-8 h-8 text-primary-600 animate-spin" />
        <p className="text-sm font-medium text-slate-500">Loading publication details...</p>
      </div>
    );
  }

  return (
    <div className="max-w-5xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-slate-200">
        <div>
          <h1 className="text-xl font-bold text-navy-950 flex items-center gap-2">
            <Newspaper className="w-6 h-6 text-primary-600" />
            Newspaper & Publication Profile
          </h1>
          <p className="text-xs text-slate-500 mt-1">
            Configure masthead branding, legal press registration, and editorial masthead details
          </p>
        </div>

        <button
          onClick={handleSubmit}
          disabled={saving}
          className="px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl text-xs font-semibold shadow-xs transition flex items-center gap-2 disabled:opacity-50"
        >
          {saving ? (
            <RefreshCw className="w-4 h-4 animate-spin" />
          ) : (
            <Save className="w-4 h-4" />
          )}
          {saving ? 'Saving Profile...' : 'Save Profile Changes'}
        </button>
      </div>

      {successMsg && (
        <div className="p-3.5 bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-600 flex-shrink-0" />
          {successMsg}
        </div>
      )}

      {errorMsg && (
        <div className="p-3.5 bg-rose-50 border border-rose-200 text-rose-800 text-xs font-medium rounded-xl flex items-center gap-2">
          <AlertCircle className="w-4 h-4 text-rose-600 flex-shrink-0" />
          {errorMsg}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Basic Publication Details */}
        <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-3">
            Primary Newspaper Identity
          </h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Publication Name *
              </label>
              <input
                type="text"
                required
                value={profile.name || ''}
                onChange={(e) => setProfile({ ...profile, name: e.target.value })}
                placeholder="e.g. Telangana Samacharam"
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 font-medium text-navy-950"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Tagline / Masthead Motto
              </label>
              <input
                type="text"
                value={profile.tagline || ''}
                onChange={(e) => setProfile({ ...profile, tagline: e.target.value })}
                placeholder="e.g. The Voice of the People"
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 font-medium text-navy-950"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                RNI / Press Registration #
              </label>
              <input
                type="text"
                value={profile.registration_number || ''}
                onChange={(e) => setProfile({ ...profile, registration_number: e.target.value })}
                placeholder="e.g. TELENG/2021/12345"
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 font-medium text-navy-950"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Publisher Name
              </label>
              <input
                type="text"
                value={profile.publisher_name || ''}
                onChange={(e) => setProfile({ ...profile, publisher_name: e.target.value })}
                placeholder="e.g. K. R. Rao"
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 font-medium text-navy-950"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Editor-in-Chief
              </label>
              <input
                type="text"
                value={profile.editor_name || ''}
                onChange={(e) => setProfile({ ...profile, editor_name: e.target.value })}
                placeholder="e.g. V. Sharma"
                className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 font-medium text-navy-950"
              />
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              About Newspaper / Editorial Mission
            </label>
            <textarea
              rows={3}
              value={profile.about || ''}
              onChange={(e) => setProfile({ ...profile, about: e.target.value })}
              placeholder="Describe your publication, coverage history, and readership focus..."
              className="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 font-medium text-navy-950 resize-none"
            />
          </div>
        </div>

        {/* Contact & Office Address */}
        <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-3">
            Contact & Editorial Bureau Address
          </h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Editorial Email
              </label>
              <div className="relative">
                <input
                  type="email"
                  value={profile.email || ''}
                  onChange={(e) => setProfile({ ...profile, email: e.target.value })}
                  placeholder="editor@samacharam.com"
                  className="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 font-medium text-navy-950"
                />
                <Mail className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
                Contact Phone / Bureau Desk
              </label>
              <div className="relative">
                <input
                  type="text"
                  value={profile.phone || ''}
                  onChange={(e) => setProfile({ ...profile, phone: e.target.value })}
                  placeholder="+91 40 2345 6789"
                  className="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 font-medium text-navy-950"
                />
                <Phone className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
              </div>
            </div>
          </div>

          <div>
            <label className="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1.5">
              Press Office / Registered Address
            </label>
            <div className="relative">
              <input
                type="text"
                value={profile.address || ''}
                onChange={(e) => setProfile({ ...profile, address: e.target.value })}
                placeholder="Press House, Basheerbagh, Hyderabad, Telangana - 500029"
                className="w-full pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-hidden focus:ring-2 focus:ring-primary-500 font-medium text-navy-950"
              />
              <MapPin className="w-4 h-4 text-slate-400 absolute left-3 top-3" />
            </div>
          </div>
        </div>

        {/* Masthead Media Assets */}
        <div className="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-xs space-y-4">
          <h2 className="text-sm font-bold text-navy-950 border-b border-slate-100 pb-3">
            Masthead Logo & Digital Assets
          </h2>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div className="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
              <label className="block text-xs font-bold text-navy-900 uppercase">
                Header Logo / Masthead Artwork
              </label>
              <div className="h-24 bg-white rounded-xl border border-dashed border-slate-300 flex items-center justify-center p-2 overflow-hidden">
                {profile.logo_url ? (
                  <img
                    src={profile.logo_url}
                    alt="Masthead Logo"
                    className="max-h-full max-w-full object-contain"
                  />
                ) : (
                  <span className="text-xs text-slate-400 flex items-center gap-1.5">
                    <Upload className="w-4 h-4" /> No logo configured
                  </span>
                )}
              </div>
              <input
                type="text"
                value={profile.logo_url || ''}
                onChange={(e) => setProfile({ ...profile, logo_url: e.target.value })}
                placeholder="Logo URL e.g. /images/masthead.png"
                className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-mono"
              />
            </div>

            <div className="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
              <label className="block text-xs font-bold text-navy-900 uppercase">
                Browser Favicon / Icon
              </label>
              <div className="h-24 bg-white rounded-xl border border-dashed border-slate-300 flex items-center justify-center p-2 overflow-hidden">
                {profile.favicon_url ? (
                  <img
                    src={profile.favicon_url}
                    alt="Favicon"
                    className="w-12 h-12 object-contain"
                  />
                ) : (
                  <span className="text-xs text-slate-400 flex items-center gap-1.5">
                    <Upload className="w-4 h-4" /> No favicon configured
                  </span>
                )}
              </div>
              <input
                type="text"
                value={profile.favicon_url || ''}
                onChange={(e) => setProfile({ ...profile, favicon_url: e.target.value })}
                placeholder="Favicon URL e.g. /favicon.ico"
                className="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs font-mono"
              />
            </div>
          </div>
        </div>
      </form>
    </div>
  );
};

import React, { useState, useEffect } from 'react';
import { Search, Building, Globe, LifeBuoy, FileText, ArrowRight, X } from 'lucide-react';
import { superAdminApi } from '../services/api';

interface GlobalSearchProps {
  isOpen: boolean;
  onClose: () => void;
  onNavigate: (module: string, itemId?: number) => void;
}

export default function GlobalSearch({ isOpen, onClose, onNavigate }: GlobalSearchProps) {
  const [query, setQuery] = useState('');
  const [loading, setLoading] = useState(false);
  const [results, setResults] = useState<{
    tenants: any[];
    domains: any[];
    tickets: any[];
    editions: any[];
  }>({
    tenants: [],
    domains: [],
    tickets: [],
    editions: [],
  });

  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        // toggle handled by parent
      }
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };
    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [isOpen, onClose]);

  useEffect(() => {
    if (!query.trim() || query.length < 2) {
      setResults({ tenants: [], domains: [], tickets: [], editions: [] });
      return;
    }

    const timer = setTimeout(async () => {
      try {
        setLoading(true);
        const [tenantsRes, domainsRes, ticketsRes, editionsRes] = await Promise.all([
          superAdminApi.tenants.list({ search: query, per_page: 4 }),
          superAdminApi.domains.list({ search: query }),
          superAdminApi.support.getTickets({ search: query }),
          superAdminApi.operations.getEditions({ search: query }),
        ]);

        const rawTenants = tenantsRes?.data || tenantsRes || [];
        const rawDomains = domainsRes?.data || domainsRes || [];
        const rawTickets = ticketsRes?.data || ticketsRes || [];
        const rawEditions = editionsRes?.data || editionsRes || [];

        setResults({
          tenants: rawTenants.slice(0, 3),
          domains: rawDomains.slice(0, 3),
          tickets: rawTickets.slice(0, 3),
          editions: rawEditions.slice(0, 3),
        });
      } catch (err) {
        console.error('Search failed', err);
      } finally {
        setLoading(false);
      }
    }, 250);

    return () => clearTimeout(timer);
  }, [query]);

  if (!isOpen) return null;

  const totalResults = results.tenants.length + results.domains.length + results.tickets.length + results.editions.length;

  return (
    <div className="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-start justify-center pt-20 p-4">
      <div 
        className="w-full max-w-2xl bg-white rounded-2xl shadow-2xl border border-slate-200 overflow-hidden transform transition-all"
        onClick={(e) => e.stopPropagation()}
      >
        {/* Search Input Bar */}
        <div className="relative border-b border-slate-200 flex items-center px-4">
          <Search className="w-5 h-5 text-slate-400" />
          <input
            type="text"
            autoFocus
            value={query}
            onChange={(e) => setQuery(e.target.value)}
            placeholder="Search tenants, domains, tickets, editions (e.g. 'Vaartha', 'epaper', 'TICK')..."
            className="w-full py-4 pl-3 pr-8 text-base text-navy-900 placeholder:text-slate-400 focus:outline-none"
          />
          {query && (
            <button onClick={() => setQuery('')} className="p-1 text-slate-400 hover:text-slate-600">
              <X className="w-4 h-4" />
            </button>
          )}
        </div>

        {/* Results Container */}
        <div className="max-h-96 overflow-y-auto p-4 space-y-4">
          {loading && (
            <p className="text-center text-xs text-slate-400 py-4">Searching platform indices...</p>
          )}

          {!loading && query.length >= 2 && totalResults === 0 && (
            <p className="text-center text-sm text-slate-500 py-6">
              No matching platform records found for "{query}".
            </p>
          )}

          {/* Tenants Section */}
          {results.tenants.length > 0 && (
            <div>
              <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1 flex items-center gap-1.5">
                <Building className="w-3.5 h-3.5 text-blue-600" />
                Newspaper Publishers & Tenants
              </div>
              <div className="space-y-1">
                {results.tenants.map((t) => (
                  <button
                    key={t.id}
                    onClick={() => {
                      onNavigate('tenants', t.id);
                      onClose();
                    }}
                    className="w-full text-left p-2.5 rounded-lg hover:bg-slate-50 flex items-center justify-between transition-colors group"
                  >
                    <div>
                      <p className="text-sm font-semibold text-navy-900 group-hover:text-blue-600">{t.newspaper_name}</p>
                      <p className="text-xs text-slate-500">{t.company_name} • {t.tenant_slug}</p>
                    </div>
                    <ArrowRight className="w-4 h-4 text-slate-400 opacity-0 group-hover:opacity-100 group-hover:text-blue-600 transition-opacity" />
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Domains Section */}
          {results.domains.length > 0 && (
            <div>
              <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1 flex items-center gap-1.5">
                <Globe className="w-3.5 h-3.5 text-blue-600" />
                Custom Domains & SSL
              </div>
              <div className="space-y-1">
                {results.domains.map((d) => (
                  <button
                    key={d.id}
                    onClick={() => {
                      onNavigate('domains');
                      onClose();
                    }}
                    className="w-full text-left p-2.5 rounded-lg hover:bg-slate-50 flex items-center justify-between transition-colors group"
                  >
                    <div>
                      <p className="text-sm font-mono font-semibold text-navy-900 group-hover:text-blue-600">{d.domain_name}</p>
                      <p className="text-xs text-slate-500">{d.tenant?.newspaper_name || 'Publisher Domain'}</p>
                    </div>
                    <ArrowRight className="w-4 h-4 text-slate-400 opacity-0 group-hover:opacity-100 group-hover:text-blue-600 transition-opacity" />
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Support Tickets Section */}
          {results.tickets.length > 0 && (
            <div>
              <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1 flex items-center gap-1.5">
                <LifeBuoy className="w-3.5 h-3.5 text-blue-600" />
                Support Tickets
              </div>
              <div className="space-y-1">
                {results.tickets.map((t) => (
                  <button
                    key={t.id}
                    onClick={() => {
                      onNavigate('support', t.id);
                      onClose();
                    }}
                    className="w-full text-left p-2.5 rounded-lg hover:bg-slate-50 flex items-center justify-between transition-colors group"
                  >
                    <div>
                      <p className="text-sm font-semibold text-navy-900 group-hover:text-blue-600">{t.subject}</p>
                      <p className="text-xs text-slate-500">{t.ticket_number || `TICK-${t.id}`} • {t.tenant?.newspaper_name}</p>
                    </div>
                    <ArrowRight className="w-4 h-4 text-slate-400 opacity-0 group-hover:opacity-100 group-hover:text-blue-600 transition-opacity" />
                  </button>
                ))}
              </div>
            </div>
          )}

          {/* Editions Section */}
          {results.editions.length > 0 && (
            <div>
              <div className="text-[11px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1 flex items-center gap-1.5">
                <FileText className="w-3.5 h-3.5 text-blue-600" />
                Newspaper Editions
              </div>
              <div className="space-y-1">
                {results.editions.map((ed) => (
                  <button
                    key={ed.id}
                    onClick={() => {
                      onNavigate('operations');
                      onClose();
                    }}
                    className="w-full text-left p-2.5 rounded-lg hover:bg-slate-50 flex items-center justify-between transition-colors group"
                  >
                    <div>
                      <p className="text-sm font-semibold text-navy-900 group-hover:text-blue-600">{ed.edition_name}</p>
                      <p className="text-xs text-slate-500">{ed.edition_code} • {ed.publish_date}</p>
                    </div>
                    <ArrowRight className="w-4 h-4 text-slate-400 opacity-0 group-hover:opacity-100 group-hover:text-blue-600 transition-opacity" />
                  </button>
                ))}
              </div>
            </div>
          )}
        </div>

        {/* Footer shortcuts */}
        <div className="p-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between text-xs text-slate-400">
          <span>Navigate with arrows, Enter to jump</span>
          <span>ESC to close</span>
        </div>
      </div>
    </div>
  );
}

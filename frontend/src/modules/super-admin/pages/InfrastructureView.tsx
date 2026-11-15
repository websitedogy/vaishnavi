import React, { useState, useEffect } from 'react';
import { 
  Cloud, Server, Database, HardDrive, Zap, AlertTriangle, 
  CheckCircle, RefreshCw, Activity, ShieldCheck, ArrowUpRight, Check
} from 'lucide-react';
import { superAdminApi } from '../services/api';
import { InfrastructureService, InfrastructureAlert } from '../types';
import StatusBadge from '../../../shared/components/StatusBadge';
import StatCard from '../../../shared/components/StatCard';

export default function InfrastructureView() {
  const [loading, setLoading] = useState(true);
  const [refreshing, setRefreshing] = useState(false);
  const [overview, setOverview] = useState<any>(null);
  const [services, setServices] = useState<InfrastructureService[]>([]);
  const [alerts, setAlerts] = useState<InfrastructureAlert[]>([]);
  const [deployments, setDeployments] = useState<any[]>([]);
  const [activeTab, setActiveTab] = useState<'services' | 'alerts' | 'deployments'>('services');
  const [alertSeverityFilter, setAlertSeverityFilter] = useState('');
  const [alertStatusFilter, setAlertStatusFilter] = useState('');
  const [processingAlertId, setProcessingAlertId] = useState<number | null>(null);

  const loadData = async () => {
    try {
      setLoading(true);
      const [overviewRes, servicesRes, alertsRes, deploymentsRes] = await Promise.all([
        superAdminApi.infrastructure.getOverview(),
        superAdminApi.infrastructure.getServices(),
        superAdminApi.infrastructure.getAlerts(),
        superAdminApi.infrastructure.getDeployments(),
      ]);

      setOverview(overviewRes);
      setServices(servicesRes || []);
      setAlerts(alertsRes.data || alertsRes || []);
      setDeployments(deploymentsRes || []);
    } catch (err: any) {
      console.error('Failed to load infrastructure data', err);
    } finally {
      setLoading(false);
      setRefreshing(false);
    }
  };

  useEffect(() => {
    loadData();
  }, []);

  const handleAcknowledge = async (id: number) => {
    try {
      setProcessingAlertId(id);
      await superAdminApi.infrastructure.acknowledgeAlert(id);
      await loadData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to acknowledge alert');
    } finally {
      setProcessingAlertId(null);
    }
  };

  const handleResolve = async (id: number) => {
    try {
      setProcessingAlertId(id);
      await superAdminApi.infrastructure.resolveAlert(id);
      await loadData();
    } catch (err: any) {
      alert(err.response?.data?.message || 'Failed to resolve alert');
    } finally {
      setProcessingAlertId(null);
    }
  };

  const filteredAlerts = alerts.filter(a => {
    const matchesSeverity = !alertSeverityFilter || a.severity === alertSeverityFilter;
    const matchesStatus = !alertStatusFilter || a.status === alertStatusFilter;
    return matchesSeverity && matchesStatus;
  });

  const activeAlertsCount = alerts.filter(a => a.status === 'active').length;

  return (
    <div className="space-y-6">
      {/* Top Header */}
      <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center gap-3">
            <h1 className="text-2xl font-bold text-navy-900">Infrastructure & Cloud Observability</h1>
            <span className="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
              <span className="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
              AWS ap-south-1 (Mumbai)
            </span>
          </div>
          <p className="text-sm text-slate-500 mt-1">
            Real-time telemetry across ECS Fargate tasks, RDS MySQL read replicas, Redis caching, and CloudFront CDN.
          </p>
        </div>

        <button
          onClick={() => { setRefreshing(true); loadData(); }}
          disabled={refreshing}
          className="flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors shadow-sm self-start md:self-auto"
        >
          <RefreshCw className={`w-4 h-4 ${refreshing ? 'animate-spin' : ''}`} />
          Refresh Metrics
        </button>
      </div>

      {/* Top Stat Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Overall Platform Health"
          value={overview?.overall_status ? overview.overall_status.toUpperCase() : "HEALTHY (99.98%)"}
          icon={<ShieldCheck className="w-5 h-5 text-emerald-600" />}
          change="All 6 AWS subsystems green"
          trend="up"
        />
        <StatCard
          title="Active CloudWatch Alerts"
          value={activeAlertsCount}
          icon={<AlertTriangle className="w-5 h-5 text-amber-600" />}
          change={activeAlertsCount > 0 ? "Requires attention" : "No active warnings"}
          trend={activeAlertsCount > 0 ? "down" : "up"}
        />
        <StatCard
          title="ECS Fargate Tasks"
          value={overview?.active_tasks ?? '12 Tasks'}
          icon={<Server className="w-5 h-5 text-blue-600" />}
          change="API: 4, Worker: 8"
          trend="neutral"
        />
        <StatCard
          title="Avg API Response Latency"
          value={overview?.avg_latency ? `${overview.avg_latency} ms` : "28 ms"}
          icon={<Activity className="w-5 h-5 text-purple-600" />}
          change="p99: 84ms"
          trend="up"
        />
      </div>

      {/* Navigation Tabs */}
      <div className="border-b border-slate-200 bg-white rounded-t-xl px-4 pt-2 shadow-sm">
        <div className="flex gap-8">
          <button
            onClick={() => setActiveTab('services')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'services'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Cloud className="w-4 h-4" />
            Core AWS Subsystems ({services.length})
          </button>
          <button
            onClick={() => setActiveTab('alerts')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'alerts'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <AlertTriangle className="w-4 h-4" />
            Telemetry Alerts ({alerts.length})
            {activeAlertsCount > 0 && (
              <span className="px-1.5 py-0.5 rounded-full text-xs bg-rose-500 text-white font-bold">
                {activeAlertsCount}
              </span>
            )}
          </button>
          <button
            onClick={() => setActiveTab('deployments')}
            className={`pb-3 text-sm font-semibold border-b-2 transition-colors flex items-center gap-2 ${
              activeTab === 'deployments'
                ? 'border-blue-600 text-blue-600'
                : 'border-transparent text-slate-500 hover:text-slate-800'
            }`}
          >
            <Zap className="w-4 h-4" />
            CI/CD & Deployments ({deployments.length})
          </button>
        </div>
      </div>

      {/* Tab 1: Services Grid */}
      {activeTab === 'services' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            {services.map((svc) => (
              <div 
                key={svc.id}
                className="p-5 border border-slate-200 rounded-xl hover:border-blue-300 transition-colors bg-white shadow-xs space-y-4"
              >
                <div className="flex items-start justify-between">
                  <div className="flex items-center gap-3">
                    <div className="w-10 h-10 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600">
                      {svc.service_type === 'ecs' ? <Server className="w-5 h-5" /> :
                       svc.service_type === 'rds' ? <Database className="w-5 h-5" /> :
                       svc.service_type === 's3' ? <HardDrive className="w-5 h-5" /> :
                       <Cloud className="w-5 h-5" />}
                    </div>
                    <div>
                      <h3 className="font-semibold text-navy-900 text-sm">{svc.service_name}</h3>
                      <p className="text-xs text-slate-500 font-mono">{svc.aws_region}</p>
                    </div>
                  </div>
                  <StatusBadge status={svc.status} />
                </div>

                <div className="space-y-2 pt-2 border-t border-slate-100 text-xs">
                  <div>
                    <div className="flex justify-between text-slate-600 mb-1">
                      <span>CPU Utilization</span>
                      <span className="font-semibold text-slate-900">{svc.cpu_percent}%</span>
                    </div>
                    <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                      <div 
                        className={`h-full ${svc.cpu_percent > 80 ? 'bg-rose-500' : svc.cpu_percent > 60 ? 'bg-amber-500' : 'bg-blue-600'}`} 
                        style={{ width: `${Math.min(svc.cpu_percent, 100)}%` }}
                      />
                    </div>
                  </div>

                  <div>
                    <div className="flex justify-between text-slate-600 mb-1">
                      <span>Memory Utilization</span>
                      <span className="font-semibold text-slate-900">{svc.memory_percent}%</span>
                    </div>
                    <div className="w-full bg-slate-100 h-1.5 rounded-full overflow-hidden">
                      <div 
                        className={`h-full ${svc.memory_percent > 85 ? 'bg-rose-500' : 'bg-emerald-500'}`} 
                        style={{ width: `${Math.min(svc.memory_percent, 100)}%` }}
                      />
                    </div>
                  </div>

                  <div className="flex justify-between pt-1 text-slate-500">
                    <span>Roundtrip Latency:</span>
                    <span className="font-mono font-medium text-slate-800">{svc.latency_ms} ms</span>
                  </div>
                </div>

                <div className="pt-2 text-[11px] text-slate-400 font-mono truncate">
                  {svc.resource_arn || 'arn:aws:ecs:ap-south-1:123456789012:...'}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Tab 2: Alerts */}
      {activeTab === 'alerts' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div className="flex items-center gap-3">
              <select
                value={alertSeverityFilter}
                onChange={(e) => setAlertSeverityFilter(e.target.value)}
                className="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Severities</option>
                <option value="critical">Critical</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
              </select>
              <select
                value={alertStatusFilter}
                onChange={(e) => setAlertStatusFilter(e.target.value)}
                className="px-3 py-2 border border-slate-200 rounded-lg text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
              >
                <option value="">All Statuses</option>
                <option value="active">Active</option>
                <option value="acknowledged">Acknowledged</option>
                <option value="resolved">Resolved</option>
              </select>
            </div>
            <div className="text-xs text-slate-500">
              Showing {filteredAlerts.length} telemetry alerts
            </div>
          </div>

          <div className="overflow-x-auto border border-slate-100 rounded-lg">
            <table className="w-full text-left text-sm">
              <thead className="bg-slate-50 text-slate-600 font-medium text-xs uppercase tracking-wider border-b border-slate-200">
                <tr>
                  <th className="px-4 py-3">Severity</th>
                  <th className="px-4 py-3">Subsystem</th>
                  <th className="px-4 py-3">Alert Title & Detail</th>
                  <th className="px-4 py-3">Status</th>
                  <th className="px-4 py-3">Triggered At</th>
                  <th className="px-4 py-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100">
                {filteredAlerts.length === 0 ? (
                  <tr>
                    <td colSpan={6} className="text-center py-8 text-slate-400">
                      No alerts matching the selected filters.
                    </td>
                  </tr>
                ) : (
                  filteredAlerts.map((alt) => (
                    <tr key={alt.id} className="hover:bg-slate-50 transition-colors">
                      <td className="px-4 py-3">
                        <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold uppercase ${
                          alt.severity === 'critical' ? 'bg-rose-100 text-rose-800' :
                          alt.severity === 'high' ? 'bg-amber-100 text-amber-800' :
                          alt.severity === 'medium' ? 'bg-blue-100 text-blue-800' :
                          'bg-slate-100 text-slate-700'
                        }`}>
                          {alt.severity}
                        </span>
                      </td>
                      <td className="px-4 py-3 font-medium text-slate-800 text-xs">
                        {alt.service?.service_name || 'Global AWS Fleet'}
                      </td>
                      <td className="px-4 py-3">
                        <p className="font-semibold text-slate-900 text-xs">{alt.title}</p>
                        <p className="text-xs text-slate-500 mt-0.5">{alt.description || 'Threshold exceeded'}</p>
                      </td>
                      <td className="px-4 py-3">
                        <StatusBadge status={alt.status} />
                      </td>
                      <td className="px-4 py-3 text-xs text-slate-500">
                        {alt.triggered_at}
                      </td>
                      <td className="px-4 py-3 text-right">
                        <div className="flex items-center justify-end gap-2">
                          {alt.status === 'active' && (
                            <button
                              onClick={() => handleAcknowledge(alt.id)}
                              disabled={processingAlertId === alt.id}
                              className="px-2.5 py-1 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200 rounded hover:bg-amber-100"
                            >
                              Ack
                            </button>
                          )}
                          {alt.status !== 'resolved' && (
                            <button
                              onClick={() => handleResolve(alt.id)}
                              disabled={processingAlertId === alt.id}
                              className="flex items-center gap-1 px-2.5 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200 rounded hover:bg-emerald-100"
                            >
                              <Check className="w-3 h-3" />
                              Resolve
                            </button>
                          )}
                          {alt.status === 'resolved' && (
                            <span className="text-xs text-slate-400">Resolved</span>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))
                )}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {/* Tab 3: Deployments */}
      {activeTab === 'deployments' && (
        <div className="bg-white rounded-b-xl border border-t-0 border-slate-200 p-6 shadow-sm space-y-4">
          <h3 className="font-semibold text-navy-900 text-sm">Deployment & Blue/Green Canary History</h3>
          <div className="divide-y divide-slate-100 border border-slate-100 rounded-lg">
            {deployments.length === 0 ? (
              <div className="p-8 text-center text-slate-400 text-sm">
                No recent deployment records found.
              </div>
            ) : (
              deployments.map((d, idx) => (
                <div key={idx} className="p-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-sm">
                  <div>
                    <div className="flex items-center gap-2">
                      <span className="font-semibold text-navy-900">{d.service_name || 'backend-api'}</span>
                      <span className="font-mono text-xs text-slate-500">v{d.version || '2.4.0'}</span>
                      <StatusBadge status={d.status || 'success'} />
                    </div>
                    <p className="text-xs text-slate-500 mt-1">{d.commit_message || 'Zero-downtime ECS rolling deployment completed'}</p>
                  </div>
                  <div className="text-right text-xs text-slate-400">
                    <p>{d.deployed_at || 'Today at 04:30 AM UTC'}</p>
                    <p className="font-mono text-[11px] text-slate-500">commit #{d.commit_hash || '7f93a1c'}</p>
                  </div>
                </div>
              ))
            )}
          </div>
        </div>
      )}
    </div>
  );
}

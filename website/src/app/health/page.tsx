'use client';

import { useEffect, useState } from 'react';

interface HealthCheck {
  status: string;
  timestamp: string;
  version: string;
  environment?: string;
  checks: {
    database: boolean;
    redis: boolean;
    queues: boolean;
    signer: boolean;
    bank_sync: boolean;
    storage: boolean;
    backup: boolean;
    certificates: boolean;
    paddle: boolean;
  };
}

export default function HealthMonitoring() {
  const [health, setHealth] = useState<HealthCheck | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [lastChecked, setLastChecked] = useState<Date>(new Date());

  const fetchHealth = async () => {
    setLoading(true);
    setError(null);

    try {
      const response = await fetch('/health');
      const data = await response.json();
      setHealth(data);
      setLastChecked(new Date());
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to fetch health status');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchHealth();

    // Auto-refresh every 30 seconds
    const interval = setInterval(fetchHealth, 30000);

    return () => clearInterval(interval);
  }, []);

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'healthy':
        return 'text-green-600 bg-green-50 border-green-200';
      case 'degraded':
        return 'text-yellow-600 bg-yellow-50 border-yellow-200';
      default:
        return 'text-red-600 bg-red-50 border-red-200';
    }
  };

  const getCheckIcon = (passed: boolean) => {
    return passed ? (
      <svg className="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
      </svg>
    ) : (
      <svg className="w-5 h-5 text-red-500" fill="currentColor" viewBox="0 0 20 20">
        <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
      </svg>
    );
  };

  const getCheckLabel = (key: string) => {
    const labels: Record<string, string> = {
      database: 'Database',
      redis: 'Redis Cache',
      queues: 'Queue System',
      signer: 'XML Signer',
      bank_sync: 'Bank Sync',
      storage: 'Storage',
      backup: 'Backup Status',
      certificates: 'Certificates',
      paddle: 'Paddle Billing',
    };
    return labels[key] || key;
  };

  return (
    <div className="min-h-screen bg-gray-50 p-8">
      <div className="max-w-6xl mx-auto">
        {/* Header */}
        <div className="mb-8">
          <h1 className="text-3xl font-bold text-gray-900 mb-2">System Health Monitoring</h1>
          <p className="text-gray-600">
            Last checked: {lastChecked.toLocaleString()}
            <button
              onClick={fetchHealth}
              className="ml-4 text-blue-600 hover:text-blue-800 underline"
              disabled={loading}
            >
              {loading ? 'Refreshing...' : 'Refresh Now'}
            </button>
          </p>
        </div>

        {/* Error State */}
        {error && (
          <div className="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
            <div className="flex items-center">
              <svg className="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clipRule="evenodd" />
              </svg>
              <span className="text-red-800 font-medium">Error: {error}</span>
            </div>
          </div>
        )}

        {/* Loading State */}
        {loading && !health && (
          <div className="flex justify-center items-center py-12">
            <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
          </div>
        )}

        {/* Health Status */}
        {health && (
          <>
            {/* Overall Status Card */}
            <div className={`border-2 rounded-lg p-6 mb-6 ${getStatusColor(health.status)}`}>
              <div className="flex items-center justify-between">
                <div>
                  <h2 className="text-2xl font-bold mb-1">
                    {health.status === 'healthy' ? '✓ All Systems Operational' : '⚠ System Degraded'}
                  </h2>
                  <p className="text-sm opacity-75">
                    Version: {health.version} | Environment: {health.environment || 'production'}
                  </p>
                </div>
                <div className="text-right">
                  <p className="text-xs opacity-75">Timestamp</p>
                  <p className="text-sm font-mono">{new Date(health.timestamp).toLocaleString()}</p>
                </div>
              </div>
            </div>

            {/* Component Checks Grid */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
              {Object.entries(health.checks).map(([key, passed]) => (
                <div
                  key={key}
                  className={`bg-white border rounded-lg p-4 shadow-sm ${
                    passed ? 'border-green-200' : 'border-red-200'
                  }`}
                >
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <h3 className="text-sm font-semibold text-gray-700 mb-1">
                        {getCheckLabel(key)}
                      </h3>
                      <p className={`text-xs ${passed ? 'text-green-600' : 'text-red-600'}`}>
                        {passed ? 'Operational' : 'Failed'}
                      </p>
                    </div>
                    <div>{getCheckIcon(passed)}</div>
                  </div>
                </div>
              ))}
            </div>

            {/* Additional Information */}
            <div className="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
              <h3 className="text-lg font-semibold text-blue-900 mb-3">Monitoring Information</h3>
              <div className="space-y-2 text-sm text-blue-800">
                <p>• Health checks run automatically every hour</p>
                <p>• Certificate expiry checks run daily at 8:00 AM</p>
                <p>• Backups are created daily at 2:00 AM</p>
                <p>• External monitoring is configured via UptimeRobot</p>
              </div>
              <div className="mt-4">
                <a
                  href="/documentation/MONITORING_SETUP.md"
                  className="text-blue-600 hover:text-blue-800 underline text-sm"
                >
                  View Monitoring Setup Guide →
                </a>
              </div>
            </div>

            {/* Quick Actions */}
            <div className="mt-6 flex gap-4">
              <a
                href="/admin/settings"
                className="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
              >
                Go to Settings
              </a>
              <a
                href="/admin/certificates"
                className="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition"
              >
                Manage Certificates
              </a>
            </div>
          </>
        )}
      </div>
    </div>
  );
}

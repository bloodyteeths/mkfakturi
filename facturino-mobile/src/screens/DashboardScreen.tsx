import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, RefreshControl } from 'react-native';
import { useAuth } from '../contexts/AuthContext';
import { getDashboardStats } from '../api/dashboard';
import { DashboardStats } from '../types/api';
import { formatCurrency } from '../utils/formatters';

const DashboardScreen = ({ navigation }: any) => {
  const { selectedCompany } = useAuth();
  const [stats, setStats] = useState<DashboardStats | null>(null);
  const [refreshing, setRefreshing] = useState(false);

  useEffect(() => {
    loadStats();
  }, [selectedCompany]);

  const loadStats = async () => {
    try {
      const data = await getDashboardStats();
      setStats(data);
    } catch (error) {
      console.error('Failed to load stats:', error);
    }
  };

  const onRefresh = async () => {
    setRefreshing(true);
    await loadStats();
    setRefreshing(false);
  };

  return (
    <ScrollView
      style={styles.container}
      refreshControl={<RefreshControl refreshing={refreshing} onRefresh={onRefresh} />}
    >
      <Text style={styles.company}>{selectedCompany?.name}</Text>

      <View style={styles.statsRow}>
        <View style={styles.statCard}>
          <Text style={styles.statLabel}>Unpaid</Text>
          <Text style={styles.statValue}>{formatCurrency(stats?.total_unpaid || 0)}</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statLabel}>Overdue</Text>
          <Text style={[styles.statValue, { color: '#EF4444' }]}>
            {formatCurrency(stats?.total_overdue || 0)}
          </Text>
        </View>
      </View>

      <View style={styles.statCard}>
        <Text style={styles.statLabel}>Collected</Text>
        <Text style={[styles.statValue, { color: '#10B981' }]}>
          {formatCurrency(stats?.amount_collected || 0)}
        </Text>
      </View>

      <TouchableOpacity style={styles.button} onPress={() => navigation.navigate('Invoices')}>
        <Text style={styles.buttonText}>Create Invoice</Text>
      </TouchableOpacity>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB', padding: 16 },
  company: { fontSize: 24, fontWeight: 'bold', marginBottom: 20 },
  statsRow: { flexDirection: 'row', gap: 12, marginBottom: 12 },
  statCard: { flex: 1, backgroundColor: '#fff', padding: 16, borderRadius: 12, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 2 },
  statLabel: { fontSize: 14, color: '#6B7280', marginBottom: 8 },
  statValue: { fontSize: 24, fontWeight: 'bold', color: '#111827' },
  button: { backgroundColor: '#007AFF', padding: 16, borderRadius: 12, alignItems: 'center', marginTop: 20 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
});

export default DashboardScreen;

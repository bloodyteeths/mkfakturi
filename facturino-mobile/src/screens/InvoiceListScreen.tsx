import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity } from 'react-native';
import { getInvoices } from '../api/invoices';
import { Invoice } from '../types/api';
import { formatCurrency, formatDate, getStatusColor } from '../utils/formatters';

const InvoiceListScreen = ({ navigation }: any) => {
  const [invoices, setInvoices] = useState<Invoice[]>([]);
  const [status, setStatus] = useState('');

  useEffect(() => {
    loadInvoices();
  }, [status]);

  const loadInvoices = async () => {
    try {
      const data = await getInvoices({ status });
      setInvoices(data.data || []);
    } catch (error) {
      console.error('Failed to load invoices:', error);
    }
  };

  const renderInvoice = ({ item }: { item: Invoice }) => (
    <TouchableOpacity
      style={styles.card}
      onPress={() => navigation.navigate('InvoiceDetail', { id: item.id })}
    >
      <View style={styles.row}>
        <Text style={styles.number}>{item.invoice_number}</Text>
        <View style={[styles.badge, { backgroundColor: getStatusColor(item.status) }]}>
          <Text style={styles.badgeText}>{item.status}</Text>
        </View>
      </View>
      <Text style={styles.customer}>{item.customer_name}</Text>
      <View style={styles.row}>
        <Text style={styles.date}>{formatDate(item.invoice_date)}</Text>
        <Text style={styles.amount}>{formatCurrency(item.total, item.currency.code)}</Text>
      </View>
    </TouchableOpacity>
  );

  return (
    <View style={styles.container}>
      <FlatList
        data={invoices}
        renderItem={renderInvoice}
        keyExtractor={(item) => item.id.toString()}
        contentContainerStyle={styles.list}
      />
    </View>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  list: { padding: 16 },
  card: { backgroundColor: '#fff', padding: 16, borderRadius: 12, marginBottom: 12, shadowColor: '#000', shadowOpacity: 0.05, shadowRadius: 4, elevation: 2 },
  row: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  number: { fontSize: 16, fontWeight: '600', color: '#111827' },
  badge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6 },
  badgeText: { color: '#fff', fontSize: 12, fontWeight: '600' },
  customer: { fontSize: 14, color: '#6B7280', marginVertical: 8 },
  date: { fontSize: 12, color: '#9CA3AF' },
  amount: { fontSize: 16, fontWeight: '600', color: '#111827' },
});

export default InvoiceListScreen;

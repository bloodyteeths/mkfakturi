import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, TouchableOpacity, Alert } from 'react-native';
import { getInvoice, sendInvoice } from '../api/invoices';
import { Invoice } from '../types/api';
import { formatCurrency, formatDate } from '../utils/formatters';

const InvoiceDetailScreen = ({ route }: any) => {
  const { id } = route.params;
  const [invoice, setInvoice] = useState<Invoice | null>(null);

  useEffect(() => {
    loadInvoice();
  }, [id]);

  const loadInvoice = async () => {
    try {
      const data = await getInvoice(id);
      setInvoice(data);
    } catch (error) {
      console.error('Failed to load invoice:', error);
    }
  };

  const handleSend = async () => {
    try {
      await sendInvoice(id);
      Alert.alert('Success', 'Invoice sent by email');
    } catch (error) {
      Alert.alert('Error', 'Failed to send invoice');
    }
  };

  if (!invoice) return <View style={styles.container}><Text>Loading...</Text></View>;

  return (
    <ScrollView style={styles.container}>
      <Text style={styles.number}>{invoice.invoice_number}</Text>
      <Text style={styles.customer}>{invoice.customer_name}</Text>
      <Text style={styles.date}>Date: {formatDate(invoice.invoice_date)}</Text>
      <Text style={styles.date}>Due: {formatDate(invoice.due_date)}</Text>

      <View style={styles.section}>
        <Text style={styles.label}>Total</Text>
        <Text style={styles.total}>{formatCurrency(invoice.total, invoice.currency.code)}</Text>
      </View>

      <TouchableOpacity style={styles.button} onPress={handleSend}>
        <Text style={styles.buttonText}>Send by Email</Text>
      </TouchableOpacity>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', padding: 16 },
  number: { fontSize: 24, fontWeight: 'bold', marginBottom: 8 },
  customer: { fontSize: 18, color: '#6B7280', marginBottom: 16 },
  date: { fontSize: 14, color: '#9CA3AF', marginBottom: 4 },
  section: { marginTop: 24, padding: 16, backgroundColor: '#F9FAFB', borderRadius: 8 },
  label: { fontSize: 14, color: '#6B7280' },
  total: { fontSize: 32, fontWeight: 'bold', color: '#111827', marginTop: 8 },
  button: { backgroundColor: '#007AFF', padding: 16, borderRadius: 12, alignItems: 'center', marginTop: 24 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
});

export default InvoiceDetailScreen;

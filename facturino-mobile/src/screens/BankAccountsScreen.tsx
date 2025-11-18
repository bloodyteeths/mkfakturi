import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, FlatList } from 'react-native';
import { getBankAccounts } from '../api/banking';
import { BankAccount } from '../types/api';
import { formatCurrency } from '../utils/formatters';

const BankAccountsScreen = () => {
  const [accounts, setAccounts] = useState<BankAccount[]>([]);

  useEffect(() => {
    loadAccounts();
  }, []);

  const loadAccounts = async () => {
    try {
      const data = await getBankAccounts();
      setAccounts(data);
    } catch (error) {
      console.error('Failed to load accounts:', error);
    }
  };

  const renderAccount = ({ item }: { item: BankAccount }) => (
    <View style={styles.card}>
      <Text style={styles.bank}>{item.bank_name}</Text>
      <Text style={styles.account}>****{item.account_number.slice(-4)}</Text>
      <Text style={styles.balance}>{formatCurrency(item.current_balance, item.currency)}</Text>
      <Text style={styles.sync}>Last sync: {new Date(item.last_sync_at).toLocaleDateString()}</Text>
    </View>
  );

  return (
    <View style={styles.container}>
      <FlatList data={accounts} renderItem={renderAccount} keyExtractor={(item) => item.id.toString()} contentContainerStyle={styles.list} />
    </View>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB' },
  list: { padding: 16 },
  card: { backgroundColor: '#fff', padding: 16, borderRadius: 12, marginBottom: 12 },
  bank: { fontSize: 16, fontWeight: '600', color: '#111827' },
  account: { fontSize: 14, color: '#6B7280', marginTop: 4 },
  balance: { fontSize: 20, fontWeight: 'bold', color: '#111827', marginTop: 8 },
  sync: { fontSize: 12, color: '#9CA3AF', marginTop: 8 },
});

export default BankAccountsScreen;

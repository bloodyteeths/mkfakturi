import React, { useState } from 'react';
import { View, Text, StyleSheet, TextInput, TouchableOpacity, ScrollView, Alert } from 'react-native';
import { createInvoice } from '../api/invoices';
import { CreateInvoicePayload, InvoiceItem } from '../types/api';

const CreateInvoiceScreen = ({ navigation }: any) => {
  const [customerId, setCustomerId] = useState('');
  const [items, setItems] = useState<InvoiceItem[]>([
    { name: '', description: '', quantity: 1, price: 0, tax: 0, total: 0 },
  ]);

  const handleCreate = async () => {
    if (!customerId) {
      Alert.alert('Error', 'Please select a customer');
      return;
    }

    const payload: CreateInvoicePayload = {
      customer_id: parseInt(customerId),
      invoice_date: new Date().toISOString().split('T')[0],
      due_date: new Date(Date.now() + 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
      items,
    };

    try {
      await createInvoice(payload);
      Alert.alert('Success', 'Invoice created');
      navigation.goBack();
    } catch (error) {
      Alert.alert('Error', 'Failed to create invoice');
    }
  };

  return (
    <ScrollView style={styles.container}>
      <Text style={styles.title}>Create Invoice</Text>

      <TextInput
        style={styles.input}
        placeholder="Customer ID"
        value={customerId}
        onChangeText={setCustomerId}
        keyboardType="numeric"
      />

      <TextInput
        style={styles.input}
        placeholder="Item name"
        value={items[0].name}
        onChangeText={(text) => setItems([{ ...items[0], name: text }])}
      />

      <TextInput
        style={styles.input}
        placeholder="Price"
        value={items[0].price.toString()}
        onChangeText={(text) => setItems([{ ...items[0], price: parseFloat(text) || 0 }])}
        keyboardType="decimal-pad"
      />

      <TouchableOpacity style={styles.button} onPress={handleCreate}>
        <Text style={styles.buttonText}>Create Invoice</Text>
      </TouchableOpacity>
    </ScrollView>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', padding: 16 },
  title: { fontSize: 24, fontWeight: 'bold', marginBottom: 20 },
  input: { borderWidth: 1, borderColor: '#ddd', padding: 12, borderRadius: 8, marginBottom: 12 },
  button: { backgroundColor: '#007AFF', padding: 16, borderRadius: 12, alignItems: 'center', marginTop: 20 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
});

export default CreateInvoiceScreen;

import React, { useState } from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Image, Alert } from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import { scanReceipt, createExpense } from '../api/receipts';
import { ReceiptScanResult } from '../types/api';

const ReceiptScanScreen = ({ navigation }: any) => {
  const [image, setImage] = useState<string | null>(null);
  const [result, setResult] = useState<ReceiptScanResult | null>(null);
  const [loading, setLoading] = useState(false);

  const pickImage = async () => {
    const permission = await ImagePicker.requestCameraPermissionsAsync();
    if (!permission.granted) {
      Alert.alert('Permission required', 'Camera access is needed');
      return;
    }

    const result = await ImagePicker.launchCameraAsync({
      mediaTypes: ImagePicker.MediaTypeOptions.Images,
      quality: 0.8,
    });

    if (!result.canceled) {
      setImage(result.assets[0].uri);
      handleScan(result.assets[0].uri);
    }
  };

  const handleScan = async (uri: string) => {
    setLoading(true);
    try {
      const data = await scanReceipt(uri);
      setResult(data);
    } catch (error) {
      Alert.alert('Error', 'OCR failed');
    } finally {
      setLoading(false);
    }
  };

  const handleCreateExpense = async () => {
    if (!result) return;
    try {
      await createExpense({ ...result });
      Alert.alert('Success', 'Expense created');
      navigation.goBack();
    } catch (error) {
      Alert.alert('Error', 'Failed to create expense');
    }
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Scan Receipt</Text>
      {image && <Image source={{ uri: image }} style={styles.image} />}
      {loading && <Text>Processing...</Text>}
      {result && (
        <View style={styles.result}>
          <Text>Vendor: {result.vendor_name}</Text>
          <Text>Amount: {result.amount}</Text>
          <Text>Date: {result.date}</Text>
          <TouchableOpacity style={styles.button} onPress={handleCreateExpense}>
            <Text style={styles.buttonText}>Create Expense</Text>
          </TouchableOpacity>
        </View>
      )}
      {!image && (
        <TouchableOpacity style={styles.button} onPress={pickImage}>
          <Text style={styles.buttonText}>Take Photo</Text>
        </TouchableOpacity>
      )}
    </View>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', padding: 16 },
  title: { fontSize: 24, fontWeight: 'bold', marginBottom: 20 },
  image: { width: '100%', height: 300, borderRadius: 12, marginBottom: 20 },
  result: { padding: 16, backgroundColor: '#F9FAFB', borderRadius: 12 },
  button: { backgroundColor: '#007AFF', padding: 16, borderRadius: 12, alignItems: 'center', marginTop: 20 },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
});

export default ReceiptScanScreen;

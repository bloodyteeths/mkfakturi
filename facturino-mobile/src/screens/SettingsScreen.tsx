import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, Linking } from 'react-native';
import { useAuth } from '../contexts/AuthContext';

const SettingsScreen = () => {
  const { user, selectedCompany, logout } = useAuth();

  const openWebApp = () => {
    Linking.openURL('https://your-facturino-domain.com');
  };

  return (
    <View style={styles.container}>
      <Text style={styles.title}>Settings</Text>

      <View style={styles.section}>
        <Text style={styles.label}>User</Text>
        <Text style={styles.value}>{user?.name}</Text>
        <Text style={styles.email}>{user?.email}</Text>
      </View>

      <View style={styles.section}>
        <Text style={styles.label}>Company</Text>
        <Text style={styles.value}>{selectedCompany?.name}</Text>
      </View>

      <TouchableOpacity style={styles.button} onPress={openWebApp}>
        <Text style={styles.buttonText}>Open Facturino Web</Text>
      </TouchableOpacity>

      <TouchableOpacity style={[styles.button, styles.logoutButton]} onPress={logout}>
        <Text style={styles.buttonText}>Logout</Text>
      </TouchableOpacity>
    </View>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#fff', padding: 16 },
  title: { fontSize: 24, fontWeight: 'bold', marginBottom: 20 },
  section: { marginBottom: 24, padding: 16, backgroundColor: '#F9FAFB', borderRadius: 12 },
  label: { fontSize: 12, color: '#6B7280', marginBottom: 8 },
  value: { fontSize: 16, fontWeight: '600', color: '#111827' },
  email: { fontSize: 14, color: '#9CA3AF', marginTop: 4 },
  button: { backgroundColor: '#007AFF', padding: 16, borderRadius: 12, alignItems: 'center', marginBottom: 12 },
  logoutButton: { backgroundColor: '#EF4444' },
  buttonText: { color: '#fff', fontSize: 16, fontWeight: '600' },
});

export default SettingsScreen;

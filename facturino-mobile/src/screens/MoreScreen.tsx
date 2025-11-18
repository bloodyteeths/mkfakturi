import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity } from 'react-native';

const MoreScreen = ({ navigation }: any) => {
  const menuItems = [
    { title: 'Bank Accounts', screen: 'BankAccounts' },
    { title: 'Scan Receipt', screen: 'ReceiptScan' },
    { title: 'Notifications', screen: 'Notifications' },
    { title: 'Settings', screen: 'Settings' },
  ];

  return (
    <View style={styles.container}>
      {menuItems.map((item, index) => (
        <TouchableOpacity
          key={index}
          style={styles.menuItem}
          onPress={() => navigation.navigate(item.screen)}
        >
          <Text style={styles.menuText}>{item.title}</Text>
          <Text style={styles.arrow}>›</Text>
        </TouchableOpacity>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F9FAFB', padding: 16 },
  menuItem: { backgroundColor: '#fff', padding: 16, borderRadius: 12, marginBottom: 12, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  menuText: { fontSize: 16, fontWeight: '600', color: '#111827' },
  arrow: { fontSize: 24, color: '#9CA3AF' },
});

export default MoreScreen;

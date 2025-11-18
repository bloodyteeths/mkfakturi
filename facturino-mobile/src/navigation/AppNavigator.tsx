import React from 'react';
import { NavigationContainer } from '@react-navigation/native';
import { createStackNavigator } from '@react-navigation/stack';
import { createBottomTabNavigator } from '@react-navigation/bottom-tabs';
import { useAuth } from '../contexts/AuthContext';

import LoginScreen from '../screens/LoginScreen';
import DashboardScreen from '../screens/DashboardScreen';
import InvoiceListScreen from '../screens/InvoiceListScreen';
import InvoiceDetailScreen from '../screens/InvoiceDetailScreen';
import CreateInvoiceScreen from '../screens/CreateInvoiceScreen';
import CustomerListScreen from '../screens/CustomerListScreen';
import AddCustomerScreen from '../screens/AddCustomerScreen';
import BankAccountsScreen from '../screens/BankAccountsScreen';
import ReceiptScanScreen from '../screens/ReceiptScanScreen';
import NotificationsScreen from '../screens/NotificationsScreen';
import SettingsScreen from '../screens/SettingsScreen';
import MoreScreen from '../screens/MoreScreen';

const Stack = createStackNavigator();
const Tab = createBottomTabNavigator();

const MainTabs = () => (
  <Tab.Navigator>
    <Tab.Screen name="Dashboard" component={DashboardScreen} />
    <Tab.Screen name="Invoices" component={InvoiceListScreen} />
    <Tab.Screen name="Customers" component={CustomerListScreen} />
    <Tab.Screen name="More" component={MoreScreen} />
  </Tab.Navigator>
);

const AppNavigator = () => {
  const { token, loading } = useAuth();

  if (loading) return null;

  return (
    <NavigationContainer>
      <Stack.Navigator>
        {!token ? (
          <Stack.Screen name="Login" component={LoginScreen} options={{ headerShown: false }} />
        ) : (
          <>
            <Stack.Screen name="Main" component={MainTabs} options={{ headerShown: false }} />
            <Stack.Screen name="InvoiceDetail" component={InvoiceDetailScreen} />
            <Stack.Screen name="CreateInvoice" component={CreateInvoiceScreen} />
            <Stack.Screen name="AddCustomer" component={AddCustomerScreen} />
            <Stack.Screen name="BankAccounts" component={BankAccountsScreen} />
            <Stack.Screen name="ReceiptScan" component={ReceiptScanScreen} />
            <Stack.Screen name="Notifications" component={NotificationsScreen} />
            <Stack.Screen name="Settings" component={SettingsScreen} />
          </>
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
};

export default AppNavigator;

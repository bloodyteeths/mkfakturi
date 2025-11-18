export const formatCurrency = (amount: number, currencyCode: string = 'EUR'): string => {
  return new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: currencyCode,
  }).format(amount || 0);
};

export const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
};

export const formatStatus = (status: string): string => {
  return status.replace('_', ' ').toLowerCase().replace(/\b\w/g, (l) => l.toUpperCase());
};

export const getStatusColor = (status: string): string => {
  const colors: { [key: string]: string } = {
    DRAFT: '#9CA3AF',
    SENT: '#3B82F6',
    VIEWED: '#8B5CF6',
    OVERDUE: '#EF4444',
    PAID: '#10B981',
  };
  return colors[status] || '#6B7280';
};

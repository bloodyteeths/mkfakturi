import moment from 'moment'

export default {
  expense_category_id: null,
  expense_date: moment().format('YYYY-MM-DD'),
  amount: 100,
  notes: '',
  attachment_receipt: null,
  customer_id: '',
  supplier_id: '',
  invoice_number: '',
  currency_id: '',
  payment_method_id: '',
  project_id: null,
  cost_center_id: null,
  vat_rate: 18,
  vat_amount: 0,
  tax_base: 0,
  status: 'draft',
  receiptFiles: [],
  customFields: [],
  fields: [],
  in_use: false,
  selectedCurrency: null
}

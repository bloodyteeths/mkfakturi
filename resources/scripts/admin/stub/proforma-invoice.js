import Guid from 'guid'
import invoiceItemStub from './invoice-item'
import taxStub from './tax'

export default function () {
  return {
    id: null,
    customer: null,
    template_name: '',
    tax_per_item: null,
    sales_tax_type: null,
    sales_tax_address_type: null,
    discount_per_item: null,
    proforma_invoice_date: '',
    expiry_date: '',
    proforma_invoice_number: '',
    customer_id: null,
    sub_total: 0,
    total: 0,
    tax: 0,
    notes: '',
    terms: '',
    private_notes: '',
    discount_type: 'fixed',
    discount_val: 0,
    reference_number: null,
    customer_po_number: null,
    discount: 0,
    items: [
      {
        ...invoiceItemStub,
        id: Guid.raw(),
        taxes: [{ ...taxStub, id: Guid.raw() }],
      },
    ],
    taxes: [],
    customFields: [],
    fields: [],
    selectedNote: null,
    selectedCurrency: '',
  }
}
// CLAUDE-CHECKPOINT

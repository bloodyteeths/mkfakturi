<?php

namespace App\Http\Controllers\V1\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\Customer\CustomerResource;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;

class CustomerLinkController extends Controller
{
    public function link(Request $request, Customer $customer)
    {
        $request->validate([
            'supplier_id' => ['required', 'integer'],
        ]);

        $companyId = $request->header('company');
        $supplier = Supplier::where('id', $request->supplier_id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        if ($customer->company_id != $companyId) {
            return response()->json(['error' => 'Customer does not belong to this company.'], 403);
        }

        if ($customer->linked_supplier_id) {
            return response()->json(['error' => 'Customer is already linked to a supplier. Unlink first.'], 422);
        }

        $existingLink = Customer::where('linked_supplier_id', $supplier->id)
            ->where('company_id', $companyId)
            ->exists();

        if ($existingLink) {
            return response()->json(['error' => 'This supplier is already linked to another customer.'], 422);
        }

        $customer->update(['linked_supplier_id' => $supplier->id]);

        return new CustomerResource(
            Customer::with('linkedSupplier', 'billingAddress', 'shippingAddress', 'fields')
                ->find($customer->id)
        );
    }

    public function unlink(Request $request, Customer $customer)
    {
        $customer->update(['linked_supplier_id' => null]);

        return new CustomerResource(
            Customer::with('billingAddress', 'shippingAddress', 'fields')
                ->find($customer->id)
        );
    }
}

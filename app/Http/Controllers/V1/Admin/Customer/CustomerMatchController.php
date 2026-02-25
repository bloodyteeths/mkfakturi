<?php

namespace App\Http\Controllers\V1\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Supplier;
use Illuminate\Http\Request;

class CustomerMatchController extends Controller
{
    /**
     * Find suppliers matching a tax_id (used on customer create form).
     */
    public function matchSupplier(Request $request)
    {
        $taxId = $request->query('tax_id');
        $companyId = $request->header('company');

        if (! $taxId || strlen($taxId) < 7) {
            return response()->json(['data' => null]);
        }

        $supplier = Supplier::where('company_id', $companyId)
            ->where('tax_id', $taxId)
            ->whereDoesntHave('linkedCustomer')
            ->first();

        if (! $supplier) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'id' => $supplier->id,
                'name' => $supplier->name,
                'tax_id' => $supplier->tax_id,
                'email' => $supplier->email,
            ],
        ]);
    }

    /**
     * Find customers matching a tax_id (used on supplier create form).
     */
    public function matchCustomer(Request $request)
    {
        $taxId = $request->query('tax_id');
        $companyId = $request->header('company');

        if (! $taxId || strlen($taxId) < 7) {
            return response()->json(['data' => null]);
        }

        $customer = Customer::where('company_id', $companyId)
            ->where('tax_id', $taxId)
            ->whereNull('linked_supplier_id')
            ->first();

        if (! $customer) {
            return response()->json(['data' => null]);
        }

        return response()->json([
            'data' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'tax_id' => $customer->tax_id,
                'email' => $customer->email,
            ],
        ]);
    }
}

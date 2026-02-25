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
            return response()->json(['data' => []]);
        }

        $suppliers = Supplier::where('company_id', $companyId)
            ->where('tax_id', $taxId)
            ->whereDoesntHave('linkedCustomer')
            ->get(['id', 'name', 'tax_id', 'email']);

        return response()->json(['data' => $suppliers]);
    }

    /**
     * Find customers matching a tax_id (used on supplier create form).
     */
    public function matchCustomer(Request $request)
    {
        $taxId = $request->query('tax_id');
        $companyId = $request->header('company');

        if (! $taxId || strlen($taxId) < 7) {
            return response()->json(['data' => []]);
        }

        $customers = Customer::where('company_id', $companyId)
            ->where('tax_id', $taxId)
            ->whereNull('linked_supplier_id')
            ->get(['id', 'name', 'tax_id', 'email']);

        return response()->json(['data' => $customers]);
    }
}

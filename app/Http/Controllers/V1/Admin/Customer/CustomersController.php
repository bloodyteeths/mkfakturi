<?php

namespace App\Http\Controllers\V1\Admin\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests;
use App\Http\Requests\DeleteCustomersRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomersController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Customer::class);

        $limit = $request->has('limit') ? $request->limit : 10;

        $customers = Customer::with([
                'creator',
                'billingAddress',
                'shippingAddress',
                'fields.customField',
                'fields.company',
                'company',
            ])
            ->whereCompany()
            ->applyFilters($request->all())
            ->withSum(['invoices as base_due_amount' => function ($query) {
                $query->whereCompany();
            }], 'base_due_amount')
            ->withSum(['invoices as due_amount' => function ($query) {
                $query->whereCompany();
            }], 'due_amount')
            ->paginateData($limit);

        return CustomerResource::collection($customers)
            ->additional(['meta' => [
                'customer_total_count' => Customer::whereCompany()->count(),
            ]]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Requests\CustomerRequest $request)
    {
        $this->authorize('create', Customer::class);

        $customer = Customer::createCustomer($request);

        return new CustomerResource($customer);
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Customer $customer)
    {
        $this->authorize('view', $customer);

        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Requests\CustomerRequest $request, Customer $customer)
    {
        $this->authorize('update', $customer);

        $customer = Customer::updateCustomer($request, $customer);

        if (is_string($customer)) {
            return respondJson('you_cannot_edit_currency', 'Cannot change currency once transactions created');
        }

        return new CustomerResource($customer);
    }

    /**
     * Remove a list of Customers along side all their resources (ie. Estimates, Invoices, Payments and Addresses)
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(DeleteCustomersRequest $request)
    {
        $this->authorize('delete multiple customers');

        Customer::deleteCustomers($request->ids);

        return response()->json([
            'success' => true,
        ]);
    }
}

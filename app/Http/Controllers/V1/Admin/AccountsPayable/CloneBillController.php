<?php

namespace App\Http\Controllers\V1\Admin\AccountsPayable;

use App\Http\Controllers\Controller;
use App\Http\Resources\BillResource;
use App\Models\Bill;
use App\Models\CompanySetting;
use App\Services\SerialNumberFormatter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;

class CloneBillController extends Controller
{
    /**
     * Clone a specific bill to create a new draft bill.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __invoke(Request $request, Bill $bill)
    {
        $this->authorize('create', Bill::class);

        $date = Carbon::now();

        $serial = (new SerialNumberFormatter)
            ->setModel($bill)
            ->setCompany($bill->company_id)
            ->setNextNumbers();

        $due_date = null;
        $dueDateEnabled = CompanySetting::getSetting(
            'bill_set_due_date_automatically',
            $request->header('company')
        );

        if ($dueDateEnabled === 'YES') {
            $dueDateDays = intval(CompanySetting::getSetting(
                'bill_due_date_days',
                $request->header('company')
            ));
            $due_date = Carbon::now()->addDays($dueDateDays)->format('Y-m-d');
        }

        $exchange_rate = $bill->exchange_rate ?? 1;

        $newBill = Bill::create([
            'bill_date' => $date->format('Y-m-d'),
            'due_date' => $due_date,
            'bill_number' => $serial->getNextNumber(),
            'sequence_number' => $serial->nextSequenceNumber,
            'reference_number' => $bill->reference_number,
            'supplier_id' => $bill->supplier_id,
            'company_id' => $request->header('company'),
            'status' => Bill::STATUS_DRAFT,
            'paid_status' => Bill::PAID_STATUS_UNPAID,
            'sub_total' => $bill->sub_total,
            'discount' => $bill->discount,
            'discount_type' => $bill->discount_type,
            'discount_val' => $bill->discount_val,
            'total' => $bill->total,
            'due_amount' => $bill->total,
            'tax_per_item' => $bill->tax_per_item,
            'discount_per_item' => $bill->discount_per_item,
            'tax' => $bill->tax,
            'notes' => $bill->notes,
            'exchange_rate' => $exchange_rate,
            'base_total' => $bill->total * $exchange_rate,
            'base_discount_val' => $bill->discount_val * $exchange_rate,
            'base_sub_total' => $bill->sub_total * $exchange_rate,
            'base_tax' => $bill->tax * $exchange_rate,
            'base_due_amount' => $bill->total * $exchange_rate,
            'currency_id' => $bill->currency_id,
        ]);

        $newBill->unique_hash = Hashids::connection(Bill::class)->encode($newBill->id);
        $newBill->save();
        $bill->load('items.taxes');

        $billItems = $bill->items->toArray();

        foreach ($billItems as $billItem) {
            $billItem['company_id'] = $request->header('company');
            $billItem['name'] = $billItem['name'];
            $billItem['exchange_rate'] = $exchange_rate;
            $billItem['base_price'] = $billItem['price'] * $exchange_rate;
            $billItem['base_discount_val'] = $billItem['discount_val'] * $exchange_rate;
            $billItem['base_tax'] = $billItem['tax'] * $exchange_rate;
            $billItem['base_total'] = $billItem['total'] * $exchange_rate;

            $item = $newBill->items()->create($billItem);

            if (array_key_exists('taxes', $billItem) && $billItem['taxes']) {
                foreach ($billItem['taxes'] as $tax) {
                    $tax['company_id'] = $request->header('company');

                    if ($tax['amount']) {
                        $item->taxes()->create($tax);
                    }
                }
            }
        }

        if ($bill->taxes) {
            foreach ($bill->taxes->toArray() as $tax) {
                $tax['company_id'] = $request->header('company');
                $newBill->taxes()->create($tax);
            }
        }

        if ($bill->fields()->exists()) {
            $customFields = [];

            foreach ($bill->fields as $data) {
                $customFields[] = [
                    'id' => $data->custom_field_id,
                    'value' => $data->defaultAnswer,
                ];
            }

            $newBill->addCustomFields($customFields);
        }

        $newBill->load([
            'supplier',
            'currency',
            'company',
            'creator',
            'items',
            'items.fields.customField',
            'payments',
            'taxes.taxType',
            'fields.customField',
        ]);

        return new BillResource($newBill);
    }
}

// CLAUDE-CHECKPOINT

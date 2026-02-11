<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Inventory Document Request
 *
 * Validates inventory document creation and update requests.
 * Handles validation for all three document types:
 * - receipt (приемница): requires warehouse, items with cost
 * - issue (издатница): requires warehouse, items
 * - transfer (преносница): requires source + destination warehouses, items
 */
class InventoryDocumentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'document_type' => 'required|in:receipt,issue,transfer',
            'warehouse_id' => 'required|exists:warehouses,id',
            'destination_warehouse_id' => 'required_if:document_type,transfer|nullable|exists:warehouses,id|different:warehouse_id',
            'document_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|exists:items,id',
            'items.*.quantity' => 'required|numeric|gt:0',
            'items.*.unit_cost' => 'required_if:document_type,receipt|nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'document_type.required' => 'Типот на документ е задолжителен.',
            'document_type.in' => 'Невалиден тип на документ.',
            'warehouse_id.required' => 'Магацинот е задолжителен.',
            'warehouse_id.exists' => 'Избраниот магацин не постои.',
            'destination_warehouse_id.required_if' => 'Одредишниот магацин е задолжителен за преносница.',
            'destination_warehouse_id.different' => 'Одредишниот магацин мора да биде различен од изворниот.',
            'document_date.required' => 'Датумот е задолжителен.',
            'items.required' => 'Мора да додадете барем една ставка.',
            'items.min' => 'Мора да додадете барем една ставка.',
            'items.*.item_id.required' => 'Артиклот е задолжителен за секоја ставка.',
            'items.*.item_id.exists' => 'Избраниот артикл не постои.',
            'items.*.quantity.required' => 'Количината е задолжителна за секоја ставка.',
            'items.*.quantity.gt' => 'Количината мора да биде поголема од 0.',
            'items.*.unit_cost.required_if' => 'Единечната цена е задолжителна за приемница.',
        ];
    }
}
// CLAUDE-CHECKPOINT

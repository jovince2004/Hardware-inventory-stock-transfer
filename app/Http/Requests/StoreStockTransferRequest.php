<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Uppercase every incoming item_code so "hm-001" matches "HM-001".
    protected function prepareForValidation(): void
    {
        if (is_array($this->items)) {
            $items = array_map(function ($line) {
                if (is_array($line) && isset($line['item_code'])) {
                    $line['item_code'] = strtoupper(trim((string) $line['item_code']));
                }
                return $line;
            }, $this->items);

            $this->merge(['items' => $items]);
        }
    }

    public function rules(): array
    {
        return [
            'source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'target_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:source_warehouse_id'],
            'transfer_date'       => ['required', 'date'],
            'transfer_reason'     => ['required', 'string', 'max:255'],
            'approved_by_staff'   => ['required', 'string', 'max:255'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.item_code'      => ['required', 'string', 'distinct', 'exists:items,item_code'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'target_warehouse_id.different' => 'Source and target warehouse must be different.',
            'items.required'                => 'A transfer needs at least one item.',
            'items.*.item_code.exists'      => 'Item code :input does not exist.',
            'items.*.item_code.distinct'    => 'Item code :input is listed more than once.',
            'items.*.quantity.min'          => 'Quantity must be at least 1.',
        ];
    }
}

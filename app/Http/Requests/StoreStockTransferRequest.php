<?php

namespace App\Http\Requests;

use App\Exceptions\StockTransferException;
use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    // Uppercase every incoming item_code so "hm-001" matches "HM-001".
    protected function prepareForValidation(): void
    {
        $reason = $this->input('transfer_reason');
        if (is_string($reason)) {
            $this->merge(['transfer_reason' => trim(strip_tags($reason))]);
        }

        $items = $this->input('items');
        if (!is_array($items)) {
            return;
        }

        foreach ($items as &$line) {
            if (is_array($line) && isset($line['item_code'])) {
                $line['item_code'] = strtoupper(trim(strip_tags((string) $line['item_code'])));
            }
        }
        unset($line);

        $this->merge(['items' => $items]);
    }

    public function rules(): array
    {
        return [
            'source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'target_warehouse_id' => ['required', 'integer', 'exists:warehouses,id', 'different:source_warehouse_id'],
            'transfer_date'       => ['required', 'date', 'before_or_equal:today'],
            'transfer_reason'     => ['required', 'string', 'max:255'],
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.item_code'      => ['required', 'string', 'distinct'],
            'items.*.quantity'       => ['required', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $items = $this->input('items');
                if (!is_array($items)) {
                    return;
                }

                $codes = [];
                foreach ($items as $line) {
                    if (is_array($line) && is_string($line['item_code'] ?? null) && $line['item_code'] !== '') {
                        $codes[] = $line['item_code'];
                    }
                }
                $codes = array_values(array_unique($codes));

                $existingCodes = [];
                foreach (array_chunk($codes, 500) as $codeBatch) {
                    foreach (Item::whereIn('item_code', $codeBatch)->pluck('item_code') as $code) {
                        $existingCodes[$code] = true;
                    }
                }

                foreach ($items as $index => $line) {
                    $code = is_array($line) ? ($line['item_code'] ?? null) : null;
                    if (is_string($code) && $code !== '' && !isset($existingCodes[$code])) {
                        $validator->errors()->add(
                            "items.{$index}.item_code",
                            "Item code {$code} does not exist."
                        );
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'target_warehouse_id.different' => 'Source and target warehouse must be different.',
            'transfer_date.required'        => 'Transfer date is required.',
            'transfer_date.before_or_equal' => 'Transfer date cannot be in the future.',
            'items.required'                => 'A transfer needs at least one item.',
            'items.*.item_code.distinct'    => 'Item code :input is listed more than once.',
            'items.*.quantity.min'          => 'Quantity must be at least 1.',
        ];
    }

    public function successMessage(): string
    {
        return 'Stock transfer completed.';
    }

    public function serviceErrorMessages(StockTransferException $exception): array
    {
        return match ($exception->reason) {
            StockTransferException::SAME_WAREHOUSE => [
                'target_warehouse_id' => 'Source and target warehouse must be different.',
            ],
            StockTransferException::ITEMS_REQUIRED => [
                'items' => 'A transfer needs at least one item.',
            ],
            StockTransferException::INVALID_QUANTITY => [
                'items' => 'Quantity for '.$exception->context['code'].' must be at least 1.',
            ],
            StockTransferException::ITEM_NOT_FOUND => [
                'items' => 'Item code '.$exception->context['code'].' does not exist.',
            ],
            StockTransferException::INSUFFICIENT_STOCK => [
                'items' => 'Insufficient stock for '.$exception->context['code'].': available '
                    .$exception->context['available'].', requested '.$exception->context['requested'].'.',
            ],
            default => throw $exception,
        };
    }
}

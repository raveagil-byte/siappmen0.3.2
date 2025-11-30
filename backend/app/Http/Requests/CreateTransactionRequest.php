<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'unit_id' => 'required|exists:units,id',
            'items' => 'required|array|min:1|max:50',
            'items.*.instrument_id' => 'required|exists:instruments,id',
            'items.*.quantity' => 'required|integer|min:1|max:1000',
            'items.*.notes' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'unit_id.required' => 'Unit ID is required',
            'unit_id.exists' => 'Selected unit does not exist',
            'type.required' => 'Transaction type is required',
            'type.in' => 'Transaction type must be either steril or kotor',
            'items.required' => 'At least one item is required',
            'items.array' => 'Items must be an array',
            'items.min' => 'At least one item is required',
            'items.max' => 'Maximum 50 items allowed per transaction',
            'items.*.instrument_id.required' => 'Instrument ID is required for each item',
            'items.*.instrument_id.exists' => 'Selected instrument does not exist',
            'items.*.quantity.required' => 'Quantity is required for each item',
            'items.*.quantity.integer' => 'Quantity must be a number',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            'items.*.quantity.max' => 'Quantity cannot exceed 1000',
            'items.*.notes.max' => 'Item notes cannot exceed 255 characters',
            'notes.max' => 'Transaction notes cannot exceed 1000 characters',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'unit_id' => 'unit',
            'items.*.instrument_id' => 'instrument',
            'items.*.quantity' => 'quantity',
            'items.*.notes' => 'item notes',
        ];
    }
}

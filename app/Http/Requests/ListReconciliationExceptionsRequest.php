<?php

namespace App\Http\Requests;

use App\ReconciliationExceptionStatus;
use App\ReconciliationExceptionType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListReconciliationExceptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['nullable', Rule::enum(ReconciliationExceptionType::class)],
            'status' => ['nullable', Rule::enum(ReconciliationExceptionStatus::class)],
            'q' => ['nullable', 'string', 'max:128'],
        ];
    }
}

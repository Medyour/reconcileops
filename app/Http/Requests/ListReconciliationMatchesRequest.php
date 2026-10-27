<?php

namespace App\Http\Requests;

use App\ReconciliationMatchRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListReconciliationMatchesRequest extends FormRequest
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
            'rule' => ['nullable', Rule::enum(ReconciliationMatchRule::class)],
            'q' => ['nullable', 'string', 'max:128'],
        ];
    }
}

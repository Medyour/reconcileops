<?php

namespace App\Http\Requests;

use App\ReconciliationDecisionAction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReconciliationDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app()->environment(['local', 'testing'])
            && (bool) config('reconcileops.allow_demo_decisions');
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'action' => ['required', Rule::enum(ReconciliationDecisionAction::class)],
            'explanation' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}

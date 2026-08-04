<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncPullRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'entity' => ['required', 'string', Rule::in(array_keys((array) config('sync.entities')))],
            'since' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function entity(): string
    {
        return (string) $this->validated('entity');
    }

    public function since(): int
    {
        return (int) ($this->validated('since') ?? 0);
    }
}

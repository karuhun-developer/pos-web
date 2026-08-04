<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi batch push. Catatan: entity/op yang tidak dikenal TIDAK ditolak di
 * sini — biar tiap envelope dapat alasan reject-nya sendiri di PushResult
 * (unknown_entity, dst). Di sini hanya validasi bentuk amplop.
 */
class SyncPushRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string,mixed> */
    public function rules(): array
    {
        return [
            'changes' => ['present', 'array'],
            'changes.*.id' => ['required', 'string'],
            'changes.*.entity' => ['required', 'string'],
            'changes.*.entityId' => ['nullable', 'string'],
            'changes.*.op' => ['required', 'string', 'in:insert,update,delete'],
            'changes.*.payload' => ['required', 'array'],
            'changes.*.createdAt' => ['nullable', 'integer'],
        ];
    }
}

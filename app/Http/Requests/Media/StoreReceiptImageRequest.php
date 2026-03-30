<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReceiptImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $maxKb = (int) config('image_upload.max_upload_kb');

        return [
            'receipt_image' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png',
                'max:'.$maxKb,
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'receipt_image.required' => __('validation.required', ['attribute' => 'receipt image']),
            'receipt_image.image' => __('validation.image', ['attribute' => 'receipt image']),
            'receipt_image.mimes' => __('validation.mimes', ['attribute' => 'receipt image', 'values' => 'jpg, jpeg, png']),
            'receipt_image.max' => __('validation.max.file', ['attribute' => 'receipt image', 'max' => (int) config('image_upload.max_upload_kb')]),
        ];
    }
}

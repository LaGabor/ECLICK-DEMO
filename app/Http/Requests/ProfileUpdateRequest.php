<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\Validation\HungarianInternationalPhoneRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'phone' => [
                ...HungarianInternationalPhoneRules::requiredRules(),
                Rule::unique(User::class, 'phone')
                    ->ignore($this->user()->id)
                    ->whereNull('deleted_at'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => __('messages.phone_hu_e164_invalid'),
        ];
    }
}

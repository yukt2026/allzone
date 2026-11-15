<?php

declare(strict_types=1);

namespace App\Http\Requests\Seller;

use Illuminate\Foundation\Http\FormRequest;

class SubmitKycRequest extends FormRequest
{
    public function authorize(): bool
    {
        $seller = $this->user()?->seller;
        return $seller !== null && !$seller->isEditingLocked();
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'terms_accepted' => ['accepted'],
            'declaration_accepted' => ['accepted'],
        ];
    }

    public function messages(): array
    {
        $brand = function_exists('setting') ? setting('name', 'ALLZONE') : 'ALLZONE';

        return [
            'terms_accepted.accepted' => "You must accept the {$brand} Merchant Marketplace Terms of Service.",
            'declaration_accepted.accepted' => 'You must certify that all provided business details, documents, and bank details are genuine.',
        ];
    }
}

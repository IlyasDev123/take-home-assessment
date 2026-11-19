<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Domain\Generation\Enums\ImageQuality;
use App\Domain\Generation\Enums\ImageSize;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

final class CreateGenerationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'prompt'             => ['required', 'string', 'min:3', 'max:4000'],
            'size'               => ['required', new Enum(ImageSize::class)],
            'quality'            => ['required', new Enum(ImageQuality::class)],
            'reference_images'   => ['nullable', 'array', 'max:4'],
            'reference_images.*' => ['url', 'max:2048'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'prompt.required'  => 'Please describe what you want to generate.',
            'prompt.max'       => 'Prompt cannot exceed 4,000 characters.',
            'size.required'    => 'Please select an image size.',
            'quality.required' => 'Please select an image quality.',
        ];
    }
}

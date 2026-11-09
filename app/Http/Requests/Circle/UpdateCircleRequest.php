<?php

namespace App\Http\Requests\Circle;

use App\Http\Requests\Circle\Concerns\ChoosesPlace;
use App\Models\Circle;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCircleRequest extends FormRequest
{
    use ChoosesPlace;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('circle'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:3', 'max:255', Rule::unique(Circle::class, 'name')->ignore($this->route('circle'))],
            'description' => ['required', 'string', 'min:3', 'max:500'],
            ...$this->placeRules(),
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSubscriptionPlanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
{

    $id = $this->route('id');

    $rules = [
        'name' => 'required|string|max:255',
        'duration_days' => 'required|integer|min:1',
        'product_limit' => 'required|integer|min:1',
        'description' => 'nullable|string',
        'is_active' => 'required|boolean',
    ];

    if ($id != 2) {
        $rules['price'] = 'required|numeric|min:0';
        $rules['name'] = 'required|string|max:255';
        $rules['is_active'] = 'required|boolean';
    } else {
        $rules['price'] = 'nullable|numeric|min:0';
        $rules['name'] = 'nullable|string|max:255';
        $rules['is_active'] = 'nullable|boolean';
    }

    return $rules;
}
}

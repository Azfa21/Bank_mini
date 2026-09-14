<?php

namespace App\Http\Requests\Teller;

use App\Models\DailyReport;
use Illuminate\Foundation\Http\FormRequest;

class StoreClosingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', DailyReport::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'report_date' => ['required', 'date_format:Y-m-d'],
            'physical_cash' => ['required_without:denominations', 'nullable', 'integer', 'min:0'],
            'denominations' => ['nullable', 'array'],
            'denominations.*' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

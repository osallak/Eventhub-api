<?php

namespace App\Http\Requests\Event;

use Illuminate\Foundation\Http\FormRequest;

class EventRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string',
            'event_type' => 'required|in:physical,online',
            'start_date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i',
            // ... other rules
        ];
    }

    public function messages()
    {
        return [
            'start_time.date_format' => 'The start time must be in 24-hour format (HH:mm).',
            'end_time.date_format' => 'The end time must be in 24-hour format (HH:mm).',
            'title.required' => 'The title field is required.',
            'title.max' => 'The title must not exceed 255 characters.',
            'description.required' => 'The description field is required.',
            'category.required' => 'The category field is required.',
            'event_type.required' => 'The event type field is required.',
            'event_type.in' => 'The event type must be either physical or online.',
            'start_date.required' => 'The start date field is required.',
            'start_date.date' => 'The start date must be a valid date.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes()
    {
        return [
            'start_time' => 'start time',
            'end_time' => 'end time',
            'event_type' => 'event type',
            'start_date' => 'start date',
        ];
    }
}

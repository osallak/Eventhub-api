<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function store(Request $request)
    {
        \Log::info('Event store endpoint hit', [
            'request' => $request->all(),
            'headers' => $request->headers->all(),
        ]);

        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'category' => 'required|string|max:255',
                'description' => 'required|string',
                'start_date' => 'required|date',
                'start_time' => 'required|date_format:H:i',
                'end_time' => 'required|date_format:H:i|after:start_time',
                'timezone' => 'required|string',
                'event_type' => ['required', Rule::in(['physical', 'virtual', 'hybrid'])],
                'meeting_link' => 'nullable|url|required_if:event_type,virtual,hybrid',
                'venue_name' => 'nullable|string|required_if:event_type,physical,hybrid',
                'address' => 'nullable|string',
                'city' => 'nullable|string',
                'postal_code' => 'nullable|string',
                'latitude' => 'nullable|numeric',
                'longitude' => 'nullable|numeric',
                'hide_address' => 'boolean',
                'max_participants' => 'nullable|integer|min:1',
                'min_age' => 'nullable|integer|min:0',
                'is_paid' => 'boolean',
                'price' => 'required_if:is_paid,true|nullable|numeric|min:0',
                'currency' => 'required_if:is_paid,true|nullable|string|size:3',
                'rules' => 'nullable|array',
                'rules.*' => 'string',
                'notes' => 'nullable|string',
                'status' => ['required', Rule::in(['draft', 'published'])],
            ]);

            $event = Event::create([
                ...$validated,
                'creator_id' => Auth::id(),
                'current_participants' => 1,
            ]);

            $event->participants()->attach(Auth::id());

            return response()->json([
                'status' => 'success',
                'message' => 'Event created successfully',
                'data' => $event->load(['creator', 'participants']),
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Event creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create event',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);  // Will throw 403 if not creator
        // ... update logic ...
    }

    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);  // Will throw 403 if not creator
        // ... delete logic ...
    }
}

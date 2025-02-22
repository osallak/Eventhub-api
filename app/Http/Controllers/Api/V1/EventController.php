<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Notifications\EventNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class EventController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['index']);
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

    public function join($id)
    {
        try {
            $event = Event::findOrFail($id);
            $user = auth()->user();

            // ... existing validation checks ...

            // All checks passed, join the event
            $event->participants()->attach($user->id);
            $event->increment('current_participants');

            // Notify other participants
            $otherParticipants = $event->participants()
                ->where('user_id', '!=', $user->id)
                ->get();

            foreach ($otherParticipants as $participant) {
                $participant->notify(new EventNotification(
                    $event,
                    EventNotification::TYPE_JOIN,
                    "{$user->name} has joined the event",
                    $user
                ));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully joined the event',
                'data' => $event->fresh()->load(['creator', 'participants']),
            ]);

        } catch (\Exception $e) {
            // ... existing error handling ...
        }
    }

    public function leave($id)
    {
        try {
            $event = Event::findOrFail($id);
            $user = auth()->user();

            // ... existing validation checks ...

            // All checks passed, leave the event
            $event->participants()->detach($user->id);
            $event->decrement('current_participants');

            // Notify other participants
            $otherParticipants = $event->participants()->get();
            foreach ($otherParticipants as $participant) {
                $participant->notify(new EventNotification(
                    $event,
                    EventNotification::TYPE_LEAVE,
                    "{$user->name} has left the event",
                    $user
                ));
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully left the event',
                'data' => $event->fresh()->load(['creator', 'participants']),
            ]);

        } catch (\Exception $e) {
            // ... existing error handling ...
        }
    }

    public function index(Request $request)
    {
        $query = Event::with(['creator', 'participants'])
            ->when($request->has('status') || $request->has('status'), function ($q) use ($request) {
                return $q->where('status', $request->input('status', $request->status));
            })
            ->when($request->has('category'), function ($q) use ($request) {
                return $q->where('category', $request->category);
            })
            ->when($request->has('event_type') || $request->has('eventType'), function ($q) use ($request) {
                return $q->where('event_type', $request->input('event_type', $request->eventType));
            })
            ->when($request->has('start_date') || $request->has('startDate'), function ($q) use ($request) {
                return $q->whereDate('start_date', '>=', $request->input('start_date', $request->startDate));
            })
            ->when($request->has('end_date') || $request->has('endDate'), function ($q) use ($request) {
                return $q->whereDate('start_date', '<=', $request->input('end_date', $request->endDate));
            })
            ->when($request->has('is_paid') || $request->has('isPaid'), function ($q) use ($request) {
                $isPaid = $request->input('is_paid', $request->isPaid);

                return $q->where('is_paid', filter_var($isPaid, FILTER_VALIDATE_BOOLEAN));
            })
            ->when($request->has('city'), function ($q) use ($request) {
                return $q->where('city', 'like', '%'.$request->city.'%');
            });

        // Default to published events only for public access
        if (! $request->has('status')) {
            $query->where('status', 'published');
        }

        // Add some debug logging
        \Log::info('Event query parameters', [
            'request' => $request->all(),
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
        ]);

        // Order by start_date by default
        $query->orderBy('start_date', 'asc');

        $perPage = $request->input('per_page', 10);
        $events = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $events,
            'meta' => [
                'filters' => [
                    'status' => $request->input('status'),
                    'category' => $request->input('category'),
                    'event_type' => $request->input('event_type', $request->input('eventType')),
                    'start_date' => $request->input('start_date', $request->input('startDate')),
                    'end_date' => $request->input('end_date', $request->input('endDate')),
                    'is_paid' => $request->input('is_paid', $request->input('isPaid')),
                    'city' => $request->input('city'),
                ],
            ],
        ]);
    }
}

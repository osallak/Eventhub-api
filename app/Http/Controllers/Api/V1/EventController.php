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
        $this->middleware('auth:api')->except(['index', 'show']);
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

    public function update(Request $request, $id)
    {
        try {
            $event = Event::findOrFail($id);

            // Add debugging at the very start
            \Log::info('Update event attempt - pre-auth check', [
                'user_id' => Auth::id(),
                'event_id' => $id,
                'is_authenticated' => Auth::check(),
                'token' => $request->bearerToken(),
                'request_method' => $request->method(),
                'request_path' => $request->path(),
            ]);

            // Check if user is authorized to update this event
            if ($event->creator_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to edit this event',
                    'debug' => [
                        'user_id' => Auth::id(),
                        'event_creator_id' => $event->creator_id,
                    ],
                ], 403);
            }

            // Validate only the fields that are present in the request
            $validationRules = [];
            $data = [];

            if ($request->has('title')) {
                $validationRules['title'] = 'string|max:255';
                $data['title'] = $request->title;
            }

            if ($request->has('category')) {
                $validationRules['category'] = 'string|max:255';
                $data['category'] = $request->category;
            }

            if ($request->has('description')) {
                $validationRules['description'] = 'string';
                $data['description'] = $request->description;
            }

            if ($request->has('start_date')) {
                $validationRules['start_date'] = 'date';
                $data['start_date'] = $request->start_date;
            }

            if ($request->has('start_time')) {
                $validationRules['start_time'] = 'date_format:H:i';
                $data['start_time'] = $request->start_time;
            }

            if ($request->has('end_time')) {
                $validationRules['end_time'] = 'date_format:H:i|after:start_time';
                $data['end_time'] = $request->end_time;
            }

            if ($request->has('timezone')) {
                $validationRules['timezone'] = 'string';
                $data['timezone'] = $request->timezone;
            }

            if ($request->has('event_type')) {
                $validationRules['event_type'] = Rule::in(['physical', 'virtual', 'hybrid']);
                $data['event_type'] = $request->event_type;

                // If event type is changing, validate meeting_link based on new type
                if ($request->event_type === 'virtual' || $request->event_type === 'hybrid') {
                    $validationRules['meeting_link'] = 'required|url';
                    $data['meeting_link'] = $request->meeting_link;
                } elseif ($request->event_type === 'physical') {
                    $validationRules['meeting_link'] = 'nullable';
                    $data['meeting_link'] = null; // Clear meeting link for physical events
                }
            } elseif ($request->has('meeting_link')) {
                // If only meeting_link is being updated, validate based on current event type
                if ($event->event_type === 'virtual' || $event->event_type === 'hybrid') {
                    $validationRules['meeting_link'] = 'required|url';
                    $data['meeting_link'] = $request->meeting_link;
                } else {
                    $validationRules['meeting_link'] = 'nullable';
                    $data['meeting_link'] = null;
                }
            }

            if ($request->has('venue_name')) {
                $validationRules['venue_name'] = 'nullable|string|required_if:event_type,physical,hybrid';
                $data['venue_name'] = $request->venue_name;
            }

            if ($request->has('address')) {
                $validationRules['address'] = 'nullable|string';
                $data['address'] = $request->address;
            }

            if ($request->has('city')) {
                $validationRules['city'] = 'nullable|string';
                $data['city'] = $request->city;
            }

            if ($request->has('postal_code')) {
                $validationRules['postal_code'] = 'nullable|string';
                $data['postal_code'] = $request->postal_code;
            }

            if ($request->has('hide_address')) {
                $validationRules['hide_address'] = 'boolean';
                $data['hide_address'] = $request->hide_address;
            }

            if ($request->has('max_participants')) {
                $validationRules['max_participants'] = 'nullable|integer|min:1';
                $data['max_participants'] = $request->max_participants;
            }

            if ($request->has('min_age')) {
                $validationRules['min_age'] = 'nullable|integer|min:0';
                $data['min_age'] = $request->min_age;
            }

            if ($request->has('is_paid')) {
                $validationRules['is_paid'] = 'boolean';
                $data['is_paid'] = $request->is_paid;

                // If is_paid is true, require price and currency
                if ($request->is_paid) {
                    $validationRules['price'] = 'required|numeric|min:0';
                    $validationRules['currency'] = 'required|string|size:3';
                    $data['price'] = $request->price;
                    $data['currency'] = $request->currency;
                } else {
                    // If is_paid is false, set price and currency to null
                    $data['price'] = null;
                    $data['currency'] = null;
                }
            } elseif ($request->has('price') || $request->has('currency')) {
                // If price or currency is being updated, validate based on current is_paid status
                if ($event->is_paid) {
                    $validationRules['price'] = 'required|numeric|min:0';
                    $validationRules['currency'] = 'required|string|size:3';
                    $data['price'] = $request->price;
                    $data['currency'] = $request->currency;
                } else {
                    $data['price'] = null;
                    $data['currency'] = null;
                }
            }

            if ($request->has('rules')) {
                $validationRules['rules'] = 'nullable|array';
                $validationRules['rules.*'] = 'string';
                $data['rules'] = $request->rules;
            }

            if ($request->has('notes')) {
                $validationRules['notes'] = 'nullable|string';
                $data['notes'] = $request->notes;
            }

            // Remove status validation and always set to published
            if ($request->has('status')) {
                $data['status'] = 'published';
            }

            // Validate the data
            $validated = $request->validate($validationRules);

            // Update the event
            $event->update($data);

            // Return the updated event
            return response()->json([
                'status' => 'success',
                'message' => 'Event updated successfully',
                'data' => [
                    'event' => $event->fresh()->load(['creator', 'participants']),
                ],
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Failed to update event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update event',
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $event = Event::with('participants')->findOrFail($id);

            // Add debugging
            \Log::info('Delete event attempt', [
                'user_id' => Auth::id(),
                'event_id' => $id,
                'event_creator_id' => $event->creator_id,
                'is_authenticated' => Auth::check(),
            ]);

            // Check if user is authorized to delete this event
            if ($event->creator_id !== Auth::id()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are not authorized to delete this event',
                    'debug' => [
                        'user_id' => Auth::id(),
                        'event_creator_id' => $event->creator_id,
                    ],
                ], 403);
            }

            // Get participant IDs before deleting
            $participantIds = $event->participants()
                ->where('user_id', '!=', Auth::id()) // Exclude the creator
                ->pluck('user_id')
                ->toArray();

            // Delete the event
            $event->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Event deleted successfully',
                'data' => [
                    'participant_ids' => $participantIds,
                    'participant_count' => count($participantIds),
                ],
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to delete event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete event',
            ], 500);
        }
    }

    public function join($id)
    {
        try {
            $event = Event::with(['participants'])->findOrFail($id);
            $user = auth()->user();

            // Check if event is published
            if ($event->status !== 'published') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot join an unpublished event',
                ], 400);
            }

            // Check if user is already a participant
            if ($event->participants->contains($user->id)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You are already a participant in this event',
                ], 400);
            }

            // Check if event is in the past with timezone consideration
            $eventDate = \Carbon\Carbon::parse($event->start_date);
            $eventTime = \Carbon\Carbon::parse($event->start_time)->format('H:i');
            $eventDateTime = $eventDate->copy()->setTimeFromTimeString($eventTime);

            $now = \Carbon\Carbon::now($event->timezone);

            // Add debug logging
            \Log::info('Event date comparison', [
                'event_date' => $eventDateTime->toDateTimeString(),
                'event_timezone' => $event->timezone,
                'current_date' => $now->toDateTimeString(),
                'is_past' => $eventDateTime->lt($now),
                'raw_event_data' => [
                    'start_date' => $event->start_date,
                    'start_time' => $eventTime,
                ],
            ]);

            if ($eventDateTime->lt($now)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot join a past event',
                ], 400);
            }

            // Check if event is full
            if ($event->max_participants && $event->current_participants >= $event->max_participants) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event has reached maximum participants',
                ], 400);
            }

            // All checks passed, join the event
            $event->participants()->attach($user->id);
            $event->increment('current_participants');

            // Notify the event creator
            $event->load('creator');
            $event->creator->notify(new EventNotification(
                $event,
                EventNotification::TYPE_JOIN,
                "{$user->name} has joined your event",
                $user
            ));

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully joined the event',
                'data' => $event->fresh()->load(['creator', 'participants']),
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to join event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
                'event_id' => $id,
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to join event',
            ], 500);
        }
    }

    public function leave(Event $event)
    {
        try {
            $user = Auth::user();

            // Check if event exists and is published
            if (! $event->exists() || $event->status !== 'published') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Event not found',
                ], 404);
            }

            // Check if user has joined the event
            if (! $event->participants()->where('user_id', $user->id)->exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'You have not joined this event',
                ], 400);
            }

            // Check if event is in the past with timezone consideration (matching join method)
            $eventDate = \Carbon\Carbon::parse($event->start_date);
            $eventTime = \Carbon\Carbon::parse($event->start_time)->format('H:i');
            $eventDateTime = $eventDate->copy()->setTimeFromTimeString($eventTime);

            $now = \Carbon\Carbon::now($event->timezone);

            // Add debug logging
            \Log::info('Event date comparison', [
                'event_date' => $eventDateTime->toDateTimeString(),
                'event_timezone' => $event->timezone,
                'current_date' => $now->toDateTimeString(),
                'is_past' => $eventDateTime->lt($now),
                'raw_event_data' => [
                    'start_date' => $event->start_date,
                    'start_time' => $eventTime,
                ],
            ]);

            if ($eventDateTime->lt($now)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Cannot leave a past event',
                ], 400);
            }

            // Remove user from participants
            $event->participants()->detach($user->id);
            $event->decrement('current_participants');

            // Notify event creator
            $event->creator->notify(new EventNotification(
                $event,
                EventNotification::TYPE_LEAVE,
                "{$user->name} has left your event",
                $user
            ));

            return response()->json([
                'status' => 'success',
                'message' => 'Successfully left the event',
                'data' => [
                    'event' => $event->load('participants'),
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to leave event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to leave event',
            ], 500);
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

    public function show($id)
    {
        try {
            $event = Event::with(['creator', 'participants'])
                ->where('status', 'published')
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $event,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Event not found',
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Failed to fetch event', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch event',
            ], 500);
        }
    }
}

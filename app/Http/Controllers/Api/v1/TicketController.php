<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * Display a listing of the user's tickets.
     */
    public function index(Request $request)
    {
        $tickets = $request->user()->tickets()->latest()->paginate(10);

        return \App\Helpers\ApiResponse::success($tickets, 'Tickets retrieved successfully');
    }

    /**
     * Store a newly created ticket.
     */
    public function store(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string',
            'priority' => 'sometimes|string',
            'category' => 'sometimes|string|max:100',
        ]);

        $priority = strtolower($request->priority ?? 'medium');
        if (! in_array($priority, ['low', 'medium', 'high'])) {
            $priority = 'medium';
        }

        $ticket = $request->user()->tickets()->create([
            'subject' => $request->subject,
            'message' => $request->message,
            'priority' => $priority,
            'category' => $request->category ?? 'General',
            'status' => 'open',
        ]);

        return \App\Helpers\ApiResponse::success($ticket->refresh(), 'Ticket created successfully', 201);
    }

    /**
     * Display the specified ticket.
     */
    public function show(Request $request, $id)
    {
        $ticket = $request->user()->tickets()->findOrFail($id);

        return \App\Helpers\ApiResponse::success($ticket, 'Ticket retrieved successfully');
    }

    /**
     * Update the specified ticket (e.g., mark as closed).
     */
    public function update(Request $request, $id)
    {
        $ticket = $request->user()->tickets()->findOrFail($id);

        $request->validate([
            'status' => 'in:open,closed',
            'priority' => 'in:low,medium,high',
        ]);

        $ticket->update($request->only(['status', 'priority']));

        return \App\Helpers\ApiResponse::success($ticket->fresh(), 'Ticket updated successfully');
    }

    /**
     * Remove the specified ticket.
     */
    public function destroy(Request $request, $id)
    {
        $ticket = $request->user()->tickets()->findOrFail($id);
        $ticket->delete();

        return \App\Helpers\ApiResponse::success(null, 'Ticket deleted successfully');
    }

    // --- Admin Methods ---

    /**
     * Display a listing of ALL tickets (Admin).
     */
    public function adminIndex(Request $request)
    {
        $tickets = Ticket::with('user:id,name,email')->latest()->paginate(10);

        return \App\Helpers\ApiResponse::success($tickets, 'All tickets retrieved successfully');
    }

    /**
     * Display any specified ticket (Admin).
     */
    public function adminShow(Request $request, $id)
    {
        $ticket = Ticket::with('user:id,name,email')->findOrFail($id);

        return \App\Helpers\ApiResponse::success($ticket, 'Ticket retrieved successfully');
    }

    /**
     * Update any specified ticket (Admin).
     */
    public function adminUpdate(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);

        $request->validate([
            'status' => 'in:open,pending,resolved,closed',
            'priority' => 'sometimes|string',
            'category' => 'sometimes|string|max:100',
        ]);

        $updateData = $request->only(['status', 'category']);

        if ($request->has('priority')) {
            $priority = strtolower($request->priority);
            if (in_array($priority, ['low', 'medium', 'high'])) {
                $updateData['priority'] = $priority;
            }
        }

        $ticket->update($updateData);

        return \App\Helpers\ApiResponse::success($ticket->fresh()->load('user:id,name,email'), 'Ticket updated successfully');
    }

    /**
     * Remove any specified ticket (Admin).
     */
    public function adminDestroy(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return \App\Helpers\ApiResponse::success(null, 'Ticket deleted successfully');
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TicketController extends Controller
{
    public function index()
    {
        $tickets = Ticket::with(['reporter', 'reportedUser', 'application'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Admin/Tickets/Index', [
            'tickets' => $tickets,
        ]);
    }

    public function show(Ticket $ticket)
    {
        $ticket->load(['reporter', 'reportedUser', 'application.internship.company', 'resolver']);

        return Inertia::render('Admin/Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    public function updateStatus(Request $request, Ticket $ticket)
    {
        $validated = $request->validate([
            'status' => 'required|in:open,investigating,resolved,closed',
            'resolution_notes' => 'nullable|string',
        ]);

        $ticket->update([
            'status' => $validated['status'],
            'resolution_notes' => $validated['resolution_notes'] ?? $ticket->resolution_notes,
        ]);

        if (in_array($validated['status'], ['resolved', 'closed']) && ! $ticket->resolved_at) {
            $ticket->update([
                'resolved_at' => now(),
                'resolved_by' => auth()->id(),
            ]);
        }

        return back()->with('success', 'Status tiket berhasil diperbarui.');
    }
}

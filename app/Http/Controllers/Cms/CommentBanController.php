<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\CommentBan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommentBanController extends Controller
{
    public function index(Request $request): Response
    {
        $query = CommentBan::query()
            ->with('bannedByUser:id,name')
            ->active();

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('value', 'ilike', "%{$search}%")
                    ->orWhere('reason', 'ilike', "%{$search}%");
            });
        }

        $bans = $query->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get users for user ban dropdown
        $users = User::select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return Inertia::render('Comments/Bans', [
            'bans' => $bans->through(fn (CommentBan $ban) => [
                'id' => $ban->id,
                'type' => $ban->type,
                'value' => $ban->value,
                'reason' => $ban->reason,
                'banned_by' => $ban->bannedByUser ? [
                    'id' => $ban->bannedByUser->id,
                    'name' => $ban->bannedByUser->name,
                ] : null,
                'expires_at' => $ban->expires_at,
                'is_permanent' => $ban->expires_at === null,
                'created_at' => $ban->created_at,
            ]),
            'users' => $users,
            'filters' => $request->only(['type', 'search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:email,ip,user'],
            'value' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        // Check if ban already exists
        $existing = CommentBan::where('type', $validated['type'])
            ->where('value', $validated['value'])
            ->active()
            ->first();

        if ($existing) {
            return redirect()->back()
                ->with('error', 'A ban for this '.$validated['type'].' already exists.');
        }

        CommentBan::create([
            'type' => $validated['type'],
            'value' => $validated['value'],
            'reason' => $validated['reason'],
            'banned_by' => auth()->id(),
            'expires_at' => $validated['expires_at'],
        ]);

        return redirect()->back()
            ->with('success', ucfirst($validated['type']).' ban created successfully.');
    }

    public function destroy(CommentBan $ban): RedirectResponse
    {
        $type = $ban->type;
        $ban->delete();

        return redirect()->back()
            ->with('success', ucfirst($type).' ban removed.');
    }
}

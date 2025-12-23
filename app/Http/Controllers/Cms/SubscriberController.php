<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Subscriber;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriberController extends Controller
{
    public function index(Request $request): Response
    {
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');

        $allowedSorts = ['email', 'status', 'subscribed_at', 'created_at'];
        if (! in_array($sortField, $allowedSorts)) {
            $sortField = 'created_at';
        }

        $query = Subscriber::query();

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where('email', 'like', "%{$search}%");
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Sort
        $direction = $sortDirection === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortField, $direction);

        $subscribers = $query->paginate(20)
            ->withQueryString()
            ->through(fn (Subscriber $subscriber) => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
                'status' => $subscriber->status,
                'subscribed_at' => $subscriber->subscribed_at?->format('Y-m-d H:i'),
                'unsubscribed_at' => $subscriber->unsubscribed_at?->format('Y-m-d H:i'),
                'created_at' => $subscriber->created_at->format('Y-m-d H:i'),
            ]);

        // Stats
        $stats = [
            'total' => Subscriber::count(),
            'active' => Subscriber::active()->count(),
            'inactive' => Subscriber::inactive()->count(),
        ];

        return Inertia::render('Subscribers/Index', [
            'subscribers' => $subscribers,
            'filters' => $request->only(['search', 'sort', 'direction', 'status']),
            'stats' => $stats,
        ]);
    }

    public function toggleStatus(Subscriber $subscriber): RedirectResponse
    {
        if ($subscriber->isActive()) {
            $subscriber->deactivate();
            $message = 'Subscriber deactivated successfully.';
        } else {
            $subscriber->activate();
            $message = 'Subscriber activated successfully.';
        }

        return redirect()->back()->with('success', $message);
    }

    public function destroy(Subscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        return redirect()->route('cms.subscribers.index')
            ->with('success', 'Subscriber deleted successfully.');
    }

    public function bulkDestroy(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:subscribers,id'],
        ]);

        $count = Subscriber::whereIn('id', $validated['ids'])->delete();

        return redirect()->route('cms.subscribers.index')
            ->with('success', "{$count} subscribers deleted successfully.");
    }

    public function bulkToggleStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'integer', 'exists:subscribers,id'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $subscribers = Subscriber::whereIn('id', $validated['ids'])->get();

        foreach ($subscribers as $subscriber) {
            if ($validated['status'] === 'active') {
                $subscriber->activate();
            } else {
                $subscriber->deactivate();
            }
        }

        $count = $subscribers->count();
        $statusLabel = $validated['status'] === 'active' ? 'activated' : 'deactivated';

        return redirect()->route('cms.subscribers.index')
            ->with('success', "{$count} subscribers {$statusLabel} successfully.");
    }

    public function export(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $query = Subscriber::query();

        // Apply same filters as index
        if ($request->filled('search')) {
            $query->where('email', 'like', "%{$request->get('search')}%");
        }
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $subscribers = $query->orderBy('created_at', 'desc')->get();

        $filename = 'subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($subscribers) {
            $handle = fopen('php://output', 'w');

            // CSV Header
            fputcsv($handle, ['Email', 'Status', 'Subscribed At', 'Created At']);

            // CSV Data
            foreach ($subscribers as $subscriber) {
                fputcsv($handle, [
                    $subscriber->email,
                    $subscriber->status,
                    $subscriber->subscribed_at?->format('Y-m-d H:i:s'),
                    $subscriber->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

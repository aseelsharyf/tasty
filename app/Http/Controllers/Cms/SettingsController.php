<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Settings/Index', [
            'settings' => [
                'app_name' => config('app.name'),
                'app_url' => config('app.url'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Settings update logic would go here
        // For now, just redirect back with a success message

        return redirect()->route('cms.settings')
            ->with('success', 'Settings updated successfully.');
    }
}

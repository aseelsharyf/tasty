<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\MediaFolder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MediaFolderController extends Controller
{
    /**
     * Get all folders as a tree structure.
     */
    public function index(): JsonResponse
    {
        $folders = MediaFolder::tree()->map(fn ($folder) => $this->formatFolder($folder));

        return response()->json($folders);
    }

    /**
     * Get a flat list of all folders (for dropdowns).
     */
    public function list(): JsonResponse
    {
        $folders = MediaFolder::with('parent')
            ->orderBy('name')
            ->get()
            ->map(fn ($folder) => [
                'id' => $folder->id,
                'uuid' => $folder->uuid,
                'name' => $folder->name,
                'path' => $folder->path,
                'parent_id' => $folder->parent_id,
            ]);

        return response()->json($folders);
    }

    /**
     * Create a new folder.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        $folder = MediaFolder::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'folder' => [
                    'id' => $folder->id,
                    'uuid' => $folder->uuid,
                    'name' => $folder->name,
                    'path' => $folder->path,
                    'parent_id' => $folder->parent_id,
                ],
            ]);
        }

        return redirect()->back()
            ->with('success', 'Folder created successfully.');
    }

    /**
     * Update a folder.
     */
    public function update(Request $request, MediaFolder $folder): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        // Prevent setting itself as parent
        if (! empty($validated['parent_id']) && $validated['parent_id'] == $folder->id) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'A folder cannot be its own parent.'], 422);
            }

            return redirect()->back()->with('error', 'A folder cannot be its own parent.');
        }

        // Prevent setting a descendant as parent (would create circular reference)
        if (! empty($validated['parent_id'])) {
            $descendantIds = $this->getDescendantIds($folder);
            if (in_array($validated['parent_id'], $descendantIds)) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'Cannot move folder into its own subfolder.'], 422);
                }

                return redirect()->back()->with('error', 'Cannot move folder into its own subfolder.');
            }
        }

        $folder->update($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'folder' => [
                    'id' => $folder->id,
                    'uuid' => $folder->uuid,
                    'name' => $folder->name,
                    'path' => $folder->path,
                    'parent_id' => $folder->parent_id,
                ],
            ]);
        }

        return redirect()->back()
            ->with('success', 'Folder updated successfully.');
    }

    /**
     * Delete a folder.
     */
    public function destroy(MediaFolder $folder): JsonResponse|RedirectResponse
    {
        // Check if folder has media items
        $itemCount = $folder->items()->count();
        $childCount = $folder->children()->count();

        if ($itemCount > 0 || $childCount > 0) {
            $message = 'Cannot delete folder. ';
            if ($itemCount > 0) {
                $message .= "It contains {$itemCount} media item(s). ";
            }
            if ($childCount > 0) {
                $message .= "It contains {$childCount} subfolder(s).";
            }

            if (request()->wantsJson()) {
                return response()->json(['error' => $message], 422);
            }

            return redirect()->back()->with('error', $message);
        }

        $folder->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back()
            ->with('success', 'Folder deleted successfully.');
    }

    /**
     * Format a folder for tree display.
     *
     * @return array<string, mixed>
     */
    private function formatFolder(MediaFolder $folder): array
    {
        $result = [
            'id' => $folder->id,
            'uuid' => $folder->uuid,
            'name' => $folder->name,
        ];

        if ($folder->children && $folder->children->count() > 0) {
            $result['children'] = $folder->children->map(fn ($child) => $this->formatFolder($child))->all();
        }

        return $result;
    }

    /**
     * Get all descendant folder IDs.
     *
     * @return array<int>
     */
    private function getDescendantIds(MediaFolder $folder): array
    {
        $ids = [];

        foreach ($folder->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }

        return $ids;
    }
}

<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Models\MediaFolder;
use App\Models\MediaItem;
use App\Models\MediaItemCrop;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\User;
use App\Services\BlurHashService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MediaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = MediaItem::query()
            ->with(['folder', 'uploadedBy', 'creditUser', 'media', 'tags'])
            ->withoutTrashed();

        // Filter by type
        if ($request->filled('type')) {
            $type = $request->get('type');
            if ($type === 'images') {
                $query->images();
            } elseif ($type === 'videos') {
                $query->videos();
            }
        }

        // Filter by folder
        if ($request->filled('folder')) {
            $folderId = $request->get('folder');
            if ($folderId === 'root') {
                $query->whereNull('folder_id');
            } else {
                $query->where('folder_id', $folderId);
            }
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        // Search (includes tag names)
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw('title::text ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('caption::text ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('description::text ILIKE ?', ["%{$search}%"])
                    ->orWhere('credit_name', 'ilike', "%{$search}%")
                    ->orWhereHas('tags', function ($tagQuery) use ($search) {
                        $tagQuery->whereRaw('name::text ILIKE ?', ["%{$search}%"]);
                    });
            });
        }

        // Filter by tags (multi-select)
        if ($request->filled('tags')) {
            $tagIds = $request->get('tags');
            if (is_array($tagIds) && count($tagIds) > 0) {
                $query->whereHas('tags', function ($q) use ($tagIds) {
                    $q->whereIn('tags.id', $tagIds);
                });
            }
        }

        // Sort
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $media = $query->paginate(24)->withQueryString();

        // Get media categories for building counts
        $mediaCategories = Setting::getMediaCategories();

        // Get counts - overall and per category
        $counts = [
            'all' => MediaItem::withoutTrashed()->count(),
            'images' => MediaItem::withoutTrashed()->images()->count(),
            'videos' => MediaItem::withoutTrashed()->videos()->count(),
            'trashed' => MediaItem::onlyTrashed()->count(),
            'by_category' => [],
        ];

        // Build category counts with type breakdown
        foreach ($mediaCategories as $category) {
            $categorySlug = $category['slug'];
            $counts['by_category'][$categorySlug] = [
                'all' => MediaItem::withoutTrashed()->where('category', $categorySlug)->count(),
                'images' => MediaItem::withoutTrashed()->where('category', $categorySlug)->images()->count(),
                'videos' => MediaItem::withoutTrashed()->where('category', $categorySlug)->videos()->count(),
            ];
        }

        // Get folders tree
        $folders = MediaFolder::tree()->map(fn ($folder) => $this->formatFolderForTree($folder));

        // Get languages for translatable fields
        $languages = Language::where('is_active', true)->orderBy('order')->get();

        // Get users for credit dropdown with their primary role
        $users = User::with('roles')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Developer', 'Writer', 'Editor', 'Photographer']))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $this->mapRoleToCreditRole($user->roles->first()?->name),
            ]);

        // Get all tags for the tags selector
        $tags = Tag::orderBy('name->en')->get(['id', 'name', 'slug']);

        return Inertia::render('Media/Index', [
            'media' => $media->through(fn (MediaItem $item) => $this->formatMediaItem($item)),
            'counts' => $counts,
            'folders' => $folders,
            'languages' => $languages,
            'users' => $users,
            'tags' => $tags,
            'creditRoles' => MediaItem::getCreditRoles(),
            'mediaCategories' => $mediaCategories,
            'filters' => $request->only(['type', 'folder', 'search', 'sort', 'direction', 'tags', 'category']),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            // File upload
            'file' => ['nullable', 'file', 'max:102400'], // 100MB max
            // Video embed
            'embed_url' => ['nullable', 'url'],
            // Category
            'category' => ['nullable', 'string', 'max:50'],
            // Translatable fields (can be array for full form or string for quick upload)
            'title' => ['nullable'],
            'caption' => ['nullable'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:5000'],
            'alt_text' => ['nullable', 'array'],
            'alt_text.*' => ['nullable', 'string', 'max:255'],
            // Credits
            'credit_user_id' => ['nullable', 'exists:users,id'],
            'credit_name' => ['nullable', 'string', 'max:255'],
            'credit_url' => ['nullable', 'url', 'max:255'],
            'credit_role' => ['nullable', 'string', 'in:photographer,videographer,illustrator,other'],
            // Organization
            'folder_id' => ['nullable', 'exists:media_folders,id'],
            // Tags
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'tags' => ['nullable', 'string'], // Comma-separated tag names (for quick upload)
            'new_tags' => ['nullable', 'array'], // New tag names to create
            'new_tags.*' => ['string', 'max:100'],
        ]);

        // Handle simple string values for title/caption (quick upload from picker)
        // Convert to array format for translatable fields
        if (! empty($validated['title']) && is_string($validated['title'])) {
            $validated['title'] = ['en' => $validated['title']];
        }
        if (! empty($validated['caption']) && is_string($validated['caption'])) {
            $validated['caption'] = ['en' => $validated['caption']];
        }

        // Handle comma-separated tags string - find or create tags
        if (! empty($validated['tags']) && empty($validated['tag_ids'])) {
            $tagNames = array_map('trim', explode(',', $validated['tags']));
            $tagIds = [];
            foreach ($tagNames as $name) {
                if (empty($name)) {
                    continue;
                }
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => ['en' => $name]]
                );
                $tagIds[] = $tag->id;
            }
            $validated['tag_ids'] = $tagIds;
        }

        // Handle new tags array - create tags and add to tag_ids
        if (! empty($validated['new_tags'])) {
            $tagIds = $validated['tag_ids'] ?? [];
            foreach ($validated['new_tags'] as $name) {
                $name = trim($name);
                if (empty($name)) {
                    continue;
                }
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => ['en' => $name]]
                );
                $tagIds[] = $tag->id;
            }
            $validated['tag_ids'] = array_unique($tagIds);
        }

        // Determine type
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mimeType = $file->getMimeType();
            $type = Str::startsWith($mimeType, 'video/') ? MediaItem::TYPE_VIDEO_LOCAL : MediaItem::TYPE_IMAGE;

            // Determine category - use provided or parse from filename
            $category = $validated['category'] ?? MediaItem::parseCategoryFromFilename($file->getClientOriginalName());

            // Wrap in transaction so if media upload fails, the DB record is rolled back
            try {
                $mediaItem = DB::transaction(function () use ($validated, $file, $mimeType, $type, $category) {
                    $mediaItem = MediaItem::create([
                        'type' => $type,
                        'category' => $category,
                        'title' => $validated['title'] ?? null,
                        'caption' => $validated['caption'] ?? null,
                        'description' => $validated['description'] ?? null,
                        'alt_text' => $validated['alt_text'] ?? null,
                        'credit_user_id' => $validated['credit_user_id'] ?? null,
                        'credit_name' => $validated['credit_name'] ?? null,
                        'credit_url' => $validated['credit_url'] ?? null,
                        'credit_role' => $validated['credit_role'] ?? null,
                        'folder_id' => $validated['folder_id'] ?? null,
                        'uploaded_by' => Auth::id(),
                        'mime_type' => $mimeType,
                        'file_size' => $file->getSize(),
                    ]);

                    // Get dimensions and blurhash BEFORE uploading to S3 (while file is still local)
                    $updateData = [];
                    if ($type === MediaItem::TYPE_IMAGE) {
                        $tempPath = $file->getRealPath();

                        // Get dimensions from the local temp file
                        $dimensions = @getimagesize($tempPath);
                        if ($dimensions) {
                            $updateData['width'] = $dimensions[0];
                            $updateData['height'] = $dimensions[1];
                        }

                        // Generate blurhash from the local temp file (skip for unsupported formats like AVIF)
                        try {
                            $blurHashService = app(BlurHashService::class);
                            $blurhash = $blurHashService->encode($tempPath);
                            if ($blurhash) {
                                $updateData['blurhash'] = $blurhash;
                            }
                        } catch (\Throwable $e) {
                            // Skip blurhash if image format not supported by GD
                        }
                    }

                    // Add media using Spatie (this uploads to S3)
                    // If this fails (e.g., MIME type rejected), the transaction will rollback
                    $spatieMedia = $mediaItem->addMediaFromRequest('file')
                        ->toMediaCollection('default');

                    // Update with dimensions/blurhash that were calculated before upload
                    if (! empty($updateData)) {
                        $mediaItem->update($updateData);
                    }

                    // Sync tags
                    if (! empty($validated['tag_ids'])) {
                        $mediaItem->tags()->sync($validated['tag_ids']);
                    }

                    return $mediaItem;
                });
            } catch (\Throwable $e) {
                $errorMessage = $e->getMessage();

                // Make error message more user-friendly
                if (str_contains($errorMessage, 'not accepted into the collection')) {
                    $errorMessage = 'This file type is not supported. Please upload a JPG, PNG, GIF, WebP, or SVG image.';
                }

                if ($request->wantsJson()) {
                    return response()->json(['message' => $errorMessage], 422);
                }

                return redirect()->back()->with('error', $errorMessage);
            }
        } elseif (! empty($validated['embed_url'])) {
            $embedInfo = MediaItem::parseEmbedUrl($validated['embed_url']);

            if (! $embedInfo) {
                if ($request->wantsJson()) {
                    return response()->json(['error' => 'Invalid video URL. Only YouTube and Vimeo are supported.'], 422);
                }

                return redirect()->back()->with('error', 'Invalid video URL. Only YouTube and Vimeo are supported.');
            }

            $mediaItem = MediaItem::create([
                'type' => MediaItem::TYPE_VIDEO_EMBED,
                'category' => $validated['category'] ?? MediaItem::CATEGORY_MEDIA,
                'embed_url' => $validated['embed_url'],
                'embed_provider' => $embedInfo['provider'],
                'embed_video_id' => $embedInfo['video_id'],
                'embed_thumbnail_url' => $embedInfo['thumbnail_url'],
                'title' => $validated['title'] ?? null,
                'caption' => $validated['caption'] ?? null,
                'description' => $validated['description'] ?? null,
                'alt_text' => $validated['alt_text'] ?? null,
                'credit_user_id' => $validated['credit_user_id'] ?? null,
                'credit_name' => $validated['credit_name'] ?? null,
                'credit_url' => $validated['credit_url'] ?? null,
                'credit_role' => $validated['credit_role'] ?? null,
                'folder_id' => $validated['folder_id'] ?? null,
                'uploaded_by' => Auth::id(),
            ]);

            // Sync tags
            if (! empty($validated['tag_ids'])) {
                $mediaItem->tags()->sync($validated['tag_ids']);
            }
        } else {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Either a file or embed URL is required.'], 422);
            }

            return redirect()->back()->with('error', 'Either a file or embed URL is required.');
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'media' => $this->formatMediaItem($mediaItem->fresh(['folder', 'uploadedBy', 'creditUser', 'media'])),
            ]);
        }

        return redirect()->route('cms.media.index')
            ->with('success', 'Media uploaded successfully.');
    }

    public function show(MediaItem $media): Response
    {
        $media->load(['folder', 'uploadedBy', 'creditUser', 'media']);

        $languages = Language::where('is_active', true)->orderBy('order')->get();
        $users = User::with('roles')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', ['Admin', 'Developer', 'Writer', 'Editor', 'Photographer']))
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $this->mapRoleToCreditRole($user->roles->first()?->name),
            ]);
        $folders = MediaFolder::tree()->map(fn ($folder) => $this->formatFolderForTree($folder));

        return Inertia::render('Media/Show', [
            'media' => $this->formatMediaItem($media),
            'languages' => $languages,
            'users' => $users,
            'folders' => $folders,
            'creditRoles' => MediaItem::getCreditRoles(),
        ]);
    }

    public function update(Request $request, MediaItem $media): RedirectResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:50'],
            'title' => ['nullable', 'array'],
            'title.*' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'array'],
            'caption.*' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'array'],
            'description.*' => ['nullable', 'string', 'max:5000'],
            'alt_text' => ['nullable', 'array'],
            'alt_text.*' => ['nullable', 'string', 'max:255'],
            'credit_user_id' => ['nullable', 'exists:users,id'],
            'credit_name' => ['nullable', 'string', 'max:255'],
            'credit_url' => ['nullable', 'url', 'max:255'],
            'credit_role' => ['nullable', 'string', 'in:photographer,videographer,illustrator,other'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
        ]);

        // Clear credit fields based on which one is being used
        if (! empty($validated['credit_user_id'])) {
            $validated['credit_name'] = null;
            $validated['credit_url'] = null;
        } elseif (! empty($validated['credit_name'])) {
            $validated['credit_user_id'] = null;
        }

        // Extract tag_ids before update
        $tagIds = $validated['tag_ids'] ?? [];
        unset($validated['tag_ids']);

        $media->update($validated);

        // Sync tags
        $media->tags()->sync($tagIds);

        return redirect()->back()
            ->with('success', 'Media updated successfully.');
    }

    public function destroy(MediaItem $media): RedirectResponse
    {
        $media->delete();

        return redirect()->back()
            ->with('success', 'Media moved to trash.');
    }

    public function restore(string $uuid): RedirectResponse
    {
        $media = MediaItem::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $media->restore();

        return redirect()->back()
            ->with('success', 'Media restored successfully.');
    }

    public function forceDelete(string $uuid): RedirectResponse
    {
        $media = MediaItem::withTrashed()->where('uuid', $uuid)->firstOrFail();
        $media->forceDelete();

        return redirect()->route('cms.media.index')
            ->with('success', 'Media permanently deleted.');
    }

    public function bulkAction(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'string', 'in:move,delete,restore,force_delete'],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'string', 'exists:media_items,uuid'],
            'folder_id' => ['nullable', 'exists:media_folders,id'],
        ]);

        $query = MediaItem::whereIn('uuid', $validated['ids']);

        // Include trashed for restore/force_delete actions
        if (in_array($validated['action'], ['restore', 'force_delete'])) {
            $query->withTrashed();
        }

        $items = $query->get();
        $count = $items->count();

        foreach ($items as $item) {
            match ($validated['action']) {
                'move' => $item->update(['folder_id' => $validated['folder_id']]),
                'delete' => $item->delete(),
                'restore' => $item->restore(),
                'force_delete' => $item->forceDelete(),
            };
        }

        $actionLabels = [
            'move' => 'moved',
            'delete' => 'moved to trash',
            'restore' => 'restored',
            'force_delete' => 'permanently deleted',
        ];

        return redirect()->back()
            ->with('success', "{$count} items {$actionLabels[$validated['action']]}.");
    }

    public function picker(Request $request): JsonResponse
    {
        $query = MediaItem::query()
            ->with(['media', 'creditUser', 'crops.media'])
            ->withoutTrashed();

        // Filter by type
        if ($request->filled('type')) {
            $type = $request->get('type');
            if ($type === 'images') {
                $query->images();
            } elseif ($type === 'videos') {
                $query->videos();
            }
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->get('category'));
        }

        // Search (includes tag names)
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw('title::text ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('caption::text ILIKE ?', ["%{$search}%"])
                    ->orWhere('credit_name', 'ilike', "%{$search}%")
                    ->orWhereHas('tags', function ($tagQuery) use ($search) {
                        $tagQuery->whereRaw('name::text ILIKE ?', ["%{$search}%"]);
                    });
            });
        }

        $media = $query->orderBy('created_at', 'desc')
            ->paginate(24)
            ->through(fn (MediaItem $item) => [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'type' => $item->type,
                'url' => $item->url,
                'thumbnail_url' => $item->thumbnail_url,
                'title' => $item->title,
                'alt_text' => $item->alt_text,
                'caption' => $item->caption,
                'credit_display' => $item->credit_display,
                'width' => $item->width,
                'height' => $item->height,
                'blurhash' => $item->blurhash,
                'is_image' => $item->is_image,
                'is_video' => $item->is_video,
                'has_crops' => $item->is_image && $item->crops->count() > 0,
                'crops' => $item->is_image ? $item->crops->map(fn (MediaItemCrop $crop) => [
                    'id' => $crop->id,
                    'uuid' => $crop->uuid,
                    'preset_name' => $crop->preset_name,
                    'preset_label' => $crop->preset_label,
                    'label' => $crop->label,
                    'display_label' => $crop->display_label,
                    'output_width' => $crop->output_width,
                    'output_height' => $crop->output_height,
                    'url' => $crop->url,
                    'thumbnail_url' => $crop->thumbnail_url,
                ])->values()->all() : [],
            ]);

        return response()->json($media);
    }

    public function trashed(Request $request): Response
    {
        abort_unless(auth()->user()->can('media.delete'), 403);

        $query = MediaItem::onlyTrashed()
            ->with(['folder', 'uploadedBy', 'creditUser', 'media']);

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->whereRaw('title::text ILIKE ?', ["%{$search}%"])
                    ->orWhereRaw('caption::text ILIKE ?', ["%{$search}%"]);
            });
        }

        $media = $query->orderBy('deleted_at', 'desc')
            ->paginate(24)
            ->withQueryString();

        return Inertia::render('Media/Trashed', [
            'media' => $media->through(fn (MediaItem $item) => $this->formatMediaItem($item)),
            'filters' => $request->only(['search']),
        ]);
    }

    private function formatMediaItem(MediaItem $item): array
    {
        return [
            'id' => $item->id,
            'uuid' => $item->uuid,
            'type' => $item->type,
            'category' => $item->category,
            'url' => $item->url,
            'thumbnail_url' => $item->thumbnail_url,
            'embed_url' => $item->embed_url,
            'embed_provider' => $item->embed_provider,
            'embed_video_id' => $item->embed_video_id,
            'title' => $item->title,
            'title_translations' => $item->getTranslations('title'),
            'caption' => $item->caption,
            'caption_translations' => $item->getTranslations('caption'),
            'description' => $item->description,
            'description_translations' => $item->getTranslations('description'),
            'alt_text' => $item->alt_text,
            'alt_text_translations' => $item->getTranslations('alt_text'),
            'credit_user_id' => $item->credit_user_id,
            'credit_name' => $item->credit_name,
            'credit_url' => $item->credit_url,
            'credit_role' => $item->credit_role,
            'credit_display' => $item->credit_display,
            'width' => $item->width,
            'height' => $item->height,
            'blurhash' => $item->blurhash,
            'file_size' => $item->file_size,
            'mime_type' => $item->mime_type,
            'duration' => $item->duration,
            'is_image' => $item->is_image,
            'is_video' => $item->is_video,
            'folder' => $item->folder ? [
                'id' => $item->folder->id,
                'uuid' => $item->folder->uuid,
                'name' => $item->folder->name,
                'path' => $item->folder->path,
            ] : null,
            'folder_id' => $item->folder_id,
            'tags' => $item->tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->values()->all(),
            'tag_ids' => $item->tags->pluck('id')->all(),
            'uploaded_by' => $item->uploadedBy ? [
                'id' => $item->uploadedBy->id,
                'name' => $item->uploadedBy->name,
            ] : null,
            'created_at' => $item->created_at,
            'updated_at' => $item->updated_at,
            'deleted_at' => $item->deleted_at,
        ];
    }

    private function formatFolderForTree(MediaFolder $folder): array
    {
        $result = [
            'id' => $folder->id,
            'uuid' => $folder->uuid,
            'name' => $folder->name,
        ];

        if ($folder->children && $folder->children->count() > 0) {
            $result['children'] = $folder->children->map(fn ($child) => $this->formatFolderForTree($child))->all();
        }

        return $result;
    }

    /**
     * Get all tags for the media picker upload form.
     */
    public function tags(): JsonResponse
    {
        $tags = Tag::orderBy('name->en')->get(['id', 'name', 'slug']);

        return response()->json($tags);
    }

    /**
     * Get all media categories.
     */
    public function categories(): JsonResponse
    {
        return response()->json(Setting::getMediaCategories());
    }

    private function mapRoleToCreditRole(?string $roleName): ?string
    {
        return match ($roleName) {
            'Photographer' => 'photographer',
            'Writer', 'Editor' => 'other',
            default => null,
        };
    }

    /**
     * Get upload configuration (whether to use signed URLs or direct upload).
     */
    public function uploadConfig(): JsonResponse
    {
        $disk = config('media-library.disk_name', 'public');
        $diskConfig = config("filesystems.disks.{$disk}", []);
        $useSignedUrls = in_array($diskConfig['driver'] ?? '', ['s3']);

        return response()->json([
            'use_signed_urls' => $useSignedUrls,
            'disk' => $disk,
        ]);
    }

    /**
     * Generate a signed URL for direct S3 upload.
     */
    public function signedUploadUrl(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'filename' => ['required', 'string', 'max:255'],
            'content_type' => ['required', 'string', 'max:100'],
            'size' => ['required', 'integer', 'min:1', 'max:104857600'], // 100MB max
        ]);

        $disk = config('media-library.disk_name', 'public');
        $diskConfig = config("filesystems.disks.{$disk}", []);

        // Only allow signed URLs for S3-compatible disks
        if (($diskConfig['driver'] ?? '') !== 's3') {
            return response()->json(['error' => 'Signed URLs are only supported for S3 disks.'], 400);
        }

        // Validate mime type
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/avif',
            'image/svg+xml',
            'video/mp4',
            'video/quicktime',
            'video/webm',
        ];

        if (! in_array($validated['content_type'], $allowedMimeTypes)) {
            return response()->json(['error' => 'File type not allowed.'], 422);
        }

        // Generate a unique path for the upload
        $extension = pathinfo($validated['filename'], PATHINFO_EXTENSION);
        $uuid = Str::uuid()->toString();
        $path = "temp-uploads/{$uuid}.{$extension}";

        // Generate signed URL for PUT request using Laravel's temporaryUploadUrl
        $signedUrl = Storage::disk($disk)->temporaryUploadUrl(
            $path,
            now()->addMinutes(15),
            ['ContentType' => $validated['content_type']]
        );

        // temporaryUploadUrl returns ['url' => '...', 'headers' => [...]]
        $url = is_array($signedUrl) ? $signedUrl['url'] : $signedUrl;

        return response()->json([
            'signed_url' => $url,
            'path' => $path,
            'uuid' => $uuid,
            'headers' => is_array($signedUrl) ? ($signedUrl['headers'] ?? []) : [],
        ]);
    }

    /**
     * Confirm upload after file has been uploaded directly to S3.
     */
    public function confirmUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'path' => ['required', 'string', 'max:500'],
            'filename' => ['required', 'string', 'max:255'],
            'content_type' => ['required', 'string', 'max:100'],
            'size' => ['required', 'integer', 'min:1'],
            // Category
            'category' => ['nullable', 'string', 'max:50'],
            // Translatable fields
            'title' => ['nullable'],
            'caption' => ['nullable'],
            // Credits
            'credit_user_id' => ['nullable', 'exists:users,id'],
            'credit_name' => ['nullable', 'string', 'max:255'],
            'credit_url' => ['nullable', 'url', 'max:255'],
            'credit_role' => ['nullable', 'string', 'in:photographer,videographer,illustrator,other'],
            // Tags
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'new_tags' => ['nullable', 'array'],
            'new_tags.*' => ['string', 'max:100'],
        ]);

        $disk = config('media-library.disk_name', 'public');

        // Verify the file exists in S3
        if (! Storage::disk($disk)->exists($validated['path'])) {
            return response()->json(['error' => 'File not found in storage.'], 404);
        }

        // Handle simple string values for title/caption
        if (! empty($validated['title']) && is_string($validated['title'])) {
            $validated['title'] = ['en' => $validated['title']];
        }
        if (! empty($validated['caption']) && is_string($validated['caption'])) {
            $validated['caption'] = ['en' => $validated['caption']];
        }

        // Handle new tags
        if (! empty($validated['new_tags'])) {
            $tagIds = $validated['tag_ids'] ?? [];
            foreach ($validated['new_tags'] as $name) {
                $name = trim($name);
                if (empty($name)) {
                    continue;
                }
                $tag = Tag::firstOrCreate(
                    ['slug' => Str::slug($name)],
                    ['name' => ['en' => $name]]
                );
                $tagIds[] = $tag->id;
            }
            $validated['tag_ids'] = array_unique($tagIds);
        }

        // Determine type from mime type
        $mimeType = $validated['content_type'];
        $type = Str::startsWith($mimeType, 'video/') ? MediaItem::TYPE_VIDEO_LOCAL : MediaItem::TYPE_IMAGE;

        // Determine category
        $category = $validated['category'] ?? MediaItem::parseCategoryFromFilename($validated['filename']);

        try {
            $mediaItem = DB::transaction(function () use ($validated, $mimeType, $type, $category, $disk) {
                $mediaItem = MediaItem::create([
                    'type' => $type,
                    'category' => $category,
                    'title' => $validated['title'] ?? null,
                    'caption' => $validated['caption'] ?? null,
                    'credit_user_id' => $validated['credit_user_id'] ?? null,
                    'credit_name' => $validated['credit_name'] ?? null,
                    'credit_url' => $validated['credit_url'] ?? null,
                    'credit_role' => $validated['credit_role'] ?? null,
                    'uploaded_by' => Auth::id(),
                    'mime_type' => $mimeType,
                    'file_size' => $validated['size'],
                ]);

                // Add to Spatie media library directly from the temp path
                // Spatie will move the file to its proper location
                $tempPath = $validated['path'];

                $spatieMedia = $mediaItem->addMediaFromDisk($tempPath, $disk)
                    ->usingFileName($validated['filename'])
                    ->toMediaCollection('default');

                // Get dimensions and blurhash for images
                if ($type === MediaItem::TYPE_IMAGE) {
                    $updateData = [];

                    // Try to get dimensions from the uploaded file
                    $fullUrl = $spatieMedia->getUrl();
                    $imageData = @getimagesizefromstring(@file_get_contents($fullUrl));
                    if ($imageData) {
                        $updateData['width'] = $imageData[0];
                        $updateData['height'] = $imageData[1];
                    }

                    // Note: Blurhash generation for remote files is complex
                    // It would require downloading the file, which defeats the purpose
                    // Consider generating it via a queued job instead

                    if (! empty($updateData)) {
                        $mediaItem->update($updateData);
                    }
                }

                // Sync tags
                if (! empty($validated['tag_ids'])) {
                    $mediaItem->tags()->sync($validated['tag_ids']);
                }

                return $mediaItem;
            });

            return response()->json([
                'success' => true,
                'media' => $this->formatMediaItem($mediaItem->fresh(['folder', 'uploadedBy', 'creditUser', 'media', 'tags'])),
            ]);
        } catch (\Throwable $e) {
            // Clean up temp file on failure
            Storage::disk($disk)->delete($validated['path']);

            return response()->json(['error' => $e->getMessage()], 422);
        }
    }
}

<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Language;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    public function index(): JsonResponse
    {
        $languages = Language::active()
            ->ordered()
            ->get(['id', 'code', 'name', 'native_name', 'direction', 'is_default']);

        return response()->json($languages);
    }
}

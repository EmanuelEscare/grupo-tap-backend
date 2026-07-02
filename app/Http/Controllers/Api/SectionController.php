<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SectionListResource;
use App\Models\Section;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $sections = Section::query()
            ->orderBy('name')
            ->get();

        return ApiResponse::success('Sections retrieved', [
            'items' => SectionListResource::collection($sections)->resolve($request),
        ]);
    }
}

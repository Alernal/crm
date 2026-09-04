<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('service_categories', 'name')->where('user_id', $request->user()->id),
            ],
        ]);

        $category = $request->user()->serviceCategories()->create($data);

        return response()->json([
            'id'   => $category->id,
            'name' => $category->name,
        ], 201);
    }
}

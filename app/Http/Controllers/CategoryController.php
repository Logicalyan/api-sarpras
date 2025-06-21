<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Category;
use App\Custom\Format;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return Format::apiResponse(200, 'List of categories', $categories);
    }

    public function show($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return Format::apiResponse(404, 'Category not found');
        }
        return Format::apiResponse(200, 'Category detail', $category);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $category = Category::create($validated);
            DB::commit();
            return Format::apiResponse(201, 'Category created successfully', $category);
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to create category', null, $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $category = Category::find($id);
        if (!$category) {
            return Format::apiResponse(404, 'Category not found');
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string'
        ]);

        DB::beginTransaction();

        try {
            $category->update($validated);
            DB::commit();
            return Format::apiResponse(200, 'Category updated successfully', $category);
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to update category', null, $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $category = Category::find($id);
        if (!$category) {
            return Format::apiResponse(404, 'Category not found');
        }

        DB::beginTransaction();

        try {
            $category->delete();
            DB::commit();
            return Format::apiResponse(200, 'Category deleted successfully');
        } catch (\Exception $e) {
            DB::rollback();
            return Format::apiResponse(500, 'Failed to delete category', null, $e->getMessage());
        }
    }
}

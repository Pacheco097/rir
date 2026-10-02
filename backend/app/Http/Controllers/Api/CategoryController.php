<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection(Category::withCount('gaps')->orderBy('name')->get());
    }

    public function store(Request $request): CategoryResource
    {
        return new CategoryResource(Category::create($request->validate($this->rules())));
    }

    public function show(Category $category): CategoryResource
    {
        return new CategoryResource($category->loadCount('gaps'));
    }

    public function update(Request $request, Category $category): CategoryResource
    {
        $category->update($request->validate($this->rules($category)));

        return new CategoryResource($category);
    }

    public function destroy(Category $category)
    {
        abort_if($category->gaps()->exists(), 422, 'Categoria em uso. Desative-a em vez de excluir.');
        $category->delete();

        return response()->noContent();
    }

    private function rules(?Category $category = null): array
    {
        return [
            'name' => [$category ? 'sometimes' : 'required', 'string', 'max:100', Rule::unique('categories')->ignore($category)],
            'description' => ['nullable', 'string', 'max:255'],
            'color' => ['nullable', 'string', 'max:20'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}

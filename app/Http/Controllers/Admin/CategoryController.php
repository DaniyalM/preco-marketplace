<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Category::with('parent:id,name')
            ->withCount('products');

        // Filters
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('description', 'like', "%{$request->search}%");
            });
        }

        if ($request->has('parent') && $request->parent !== 'all') {
            if ($request->parent === 'root') {
                $query->whereNull('parent_id');
            } else {
                $query->where('parent_id', $request->parent);
            }
        }

        $categories = $query->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'parent' => $category->parent ? [
                    'id' => $category->parent->id,
                    'name' => $category->parent->name,
                ] : null,
                'description' => $category->description,
                'icon' => $category->icon,
                'image' => $category->image,
                'is_active' => $category->is_active,
                'is_featured' => $category->is_featured,
                'sort_order' => $category->sort_order,
                'products_count' => $category->products_count,
                'created_at' => $category->created_at->toISOString(),
            ]);

        // Parent categories for filter dropdown
        $parentCategories = Category::whereNull('parent_id')
            ->active()
            ->ordered()
            ->get(['id', 'name']);

        // Stats
        $stats = [
            'total' => Category::count(),
            'active' => Category::where('is_active', true)->count(),
            'inactive' => Category::where('is_active', false)->count(),
            'root' => Category::whereNull('parent_id')->count(),
        ];

        return Inertia::render('Admin/Categories/Index', [
            'categories' => $categories,
            'parentCategories' => $parentCategories,
            'stats' => $stats,
            'filters' => $request->only(['status', 'search', 'parent']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Ensure unique slug
        $baseSlug = $validated['slug'];
        $counter = 1;
        while (Category::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter++;
        }

        Category::create($validated);

        return back()->with('success', 'Category created successfully.');
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($category) {
                    // Prevent setting parent to itself or its descendants
                    if ($value == $category->id) {
                        $fail('A category cannot be its own parent.');
                    }
                    if ($value && in_array($value, $category->getAllDescendantIds())) {
                        $fail('Cannot set a descendant as parent.');
                    }
                },
            ],
            'description' => 'nullable|string|max:1000',
            'icon' => 'nullable|string|max:100',
            'image' => 'nullable|string|max:500',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        // Update slug if name changed
        if ($validated['name'] !== $category->name) {
            $validated['slug'] = Str::slug($validated['name']);
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Category::where('slug', $validated['slug'])->where('id', '!=', $category->id)->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter++;
            }
        }

        $category->update($validated);

        return back()->with('success', 'Category updated successfully.');
    }

    public function destroy(Category $category)
    {
        // Check if has products
        if ($category->products()->exists()) {
            return back()->with('error', 'Cannot delete category with products. Move products first.');
        }

        // Check if has children
        if ($category->children()->exists()) {
            return back()->with('error', 'Cannot delete category with subcategories. Delete subcategories first.');
        }

        $category->delete();

        return back()->with('success', 'Category deleted successfully.');
    }

    public function toggleActive(Category $category)
    {
        $category->update(['is_active' => !$category->is_active]);

        return back()->with('success', $category->is_active ? 'Category activated.' : 'Category deactivated.');
    }

    public function toggleFeatured(Category $category)
    {
        $category->update(['is_featured' => !$category->is_featured]);

        return back()->with('success', $category->is_featured ? 'Category featured.' : 'Category unfeatured.');
    }

    public function reorder(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:categories,id',
            'categories.*.sort_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['categories'] as $item) {
            Category::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Categories reordered.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryManagementController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $status = trim((string) $request->query('status', 'all'));

        $categories = Category::query()
            ->when($q !== '', fn ($query) => $query
                ->where('name', 'like', "%{$q}%")
                ->orWhere('description', 'like', "%{$q}%"))
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.categories', [
            'categories' => $categories,
            'filters' => [
                'q' => $q,
                'status' => $status,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        Category::query()->create([
            'name' => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        return back()->with('status', 'Kategori berhasil ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => trim((string) $data['name']),
            'description' => trim((string) ($data['description'] ?? '')),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        return back()->with('status', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        if ($category->products()->exists()) {
            return back()->withErrors(['category' => 'Kategori tidak bisa dihapus karena masih dipakai produk.']);
        }

        $category->delete();

        return back()->with('status', 'Kategori berhasil dihapus.');
    }
}


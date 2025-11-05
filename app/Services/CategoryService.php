<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class CategoryService
{
    public function indexQuery(array $filters)
    {
        $q = Category::query();

        if (isset($filters['parent_id'])) {
            $q->where('parent_id', $filters['parent_id']);
        }

        if (array_key_exists('active', $filters)) {
            $q->where('is_active', (bool)$filters['active']);
        }

        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $q->where(function ($w) use ($term) {
                $w->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%")
                    ->orWhere('path', 'like', "%{$term}%");
            });
        }

        return $q;
    }

    public function create(array $data): Category
    {
        return DB::transaction(function () use ($data) {

            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

            // Compute path/depth from parent
            [$data['path'], $data['depth']] = $this->computePathAndDepth($data['slug'], $data['parent_id'] ?? null);

            $category = Category::create([
                'name'      => $data['name'],
                'slug'      => $data['slug'],
                'parent_id' => $data['parent_id'] ?? null,
                'path'      => $data['path'],
                'depth'     => $data['depth'],
                'position'  => $data['position'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            return $category;
        });
    }

    public function update(Category $category, array $data): Category
    {
        return DB::transaction(function () use ($category, $data) {
            unset($data['slug']);

            $parentChanged = array_key_exists('parent_id', $data) && $data['parent_id'] !== $category->parent_id;
            if ($parentChanged) {
                [$path, $depth] = $this->computePathAndDepth($category->slug, $data['parent_id']); // slug unchanged
                $data['path']  = $path;
                $data['depth'] = $depth;
            }

            $category->update($data);

            // TODO (optional): if parent/slug changed, you might want to recalc children subtree paths.
            // This requires a subtree traversal; we can add a separate "rebuildSubtree" job if needed.

            return $category;
        });
    }

    public function destroy(Category $category): void
    {
        DB::transaction(function () use ($category) {
            // for safety
            if ($category->children()->exists()) {
                throw new RuntimeException('Cannot delete a category that has children.');
            }

            $category->products()->detach();

            $category->delete();
        });
    }


    public function childrenOf(Category $category)
    {
        return $category->children()->orderBy('position')->orderBy('name')->get();
    }

    public function descendantsIds(Category $category, bool $includeSelf = true)
    {
        $q = Category::query()->where('path', 'like', $category->path . '%');
        if (!$includeSelf) {
            $q->where('id', '<>', $category->id);
        }
        return $q->pluck('id');
    }

    public function productsParamsFor(Category $category, string $scope = 'primary', bool $includeDescendants = true): array
    {
        $ids = $includeDescendants
            ? $this->descendantsIds($category, true)->all()
            : [$category->id];

        return $scope === 'any'
            ? ['category_ids_any'     => $ids]
            : ['primary_category_ids' => $ids];
    }

    private function computePathAndDepth(string $slug, ?int $parentId): array
    {
        if ($parentId) {
            $parent = Category::findOrFail($parentId);
            return [$parent->path . '/' . $slug, $parent->depth + 1];
        }
        return [$slug, 0];
    }
}

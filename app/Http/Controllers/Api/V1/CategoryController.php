<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Category\StoreCategoryRequest;
use App\Http\Requests\Category\UpdateCategoryRequest;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 商品分類控制器
 *
 * 處理商品分類的 CRUD 操作，支援樹狀階層結構
 */
class CategoryController extends Controller
{
    use ApiResponse;

    /**
     * 取得分類列表
     */
    public function index(Request $request): JsonResponse
    {
        $query = Category::with(['parent']);

        // 搜尋條件
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%");
            });
        }

        // 父分類篩選
        if ($request->filled('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        }

        // 只顯示根分類
        if ($request->boolean('root_only')) {
            $query->whereNull('parent_id');
        }

        // 狀態篩選
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 排序
        $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');

        // 分頁
        $perPage = $request->input('per_page', 15);
        $categories = $query->paginate($perPage);

        return $this->paginated($categories, '查詢分類列表成功');
    }

    /**
     * 新增分類
     *
     * @param  StoreCategoryRequest  $request  新增分類請求
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $data = $request->validated();

        // 計算階層
        if (! empty($data['parent_id'])) {
            $parent = Category::find($data['parent_id']);
            $data['level'] = $parent->level + 1;
            $data['path'] = $parent->path.'/'.$parent->id;
        } else {
            $data['level'] = 1;
            $data['path'] = '';
        }

        $category = Category::create($data);
        $category->load(['parent']);

        return $this->created($category, '分類建立成功');
    }

    /**
     * 取得單一分類
     *
     * @param  Category  $category  分類
     */
    public function show(Category $category): JsonResponse
    {
        $category->load(['parent', 'children']);

        return $this->success($category, '取得分類詳情成功');
    }

    /**
     * 更新分類
     *
     * @param  UpdateCategoryRequest  $request  更新分類請求
     * @param  Category  $category  分類
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $data = $request->validated();

        // 如果變更父分類，重新計算階層
        if (isset($data['parent_id']) && $data['parent_id'] !== $category->parent_id) {
            // 檢查是否將自己設為自己的子分類
            if ($data['parent_id'] == $category->id) {
                return $this->error('不能將分類設為自己的子分類', 422);
            }

            // 檢查是否將自己設為自己子分類的子分類
            $descendantIds = $this->getDescendantIds($category);
            if (in_array($data['parent_id'], $descendantIds)) {
                return $this->error('不能將分類設為其子分類的子分類', 422);
            }

            if (! empty($data['parent_id'])) {
                $parent = Category::find($data['parent_id']);
                $data['level'] = $parent->level + 1;
                $data['path'] = $parent->path.'/'.$parent->id;
            } else {
                $data['level'] = 1;
                $data['path'] = '';
            }
        }

        $category->update($data);
        $category->load(['parent']);

        return $this->success($category, '分類更新成功');
    }

    /**
     * 刪除分類
     *
     * @param  Category  $category  分類
     */
    public function destroy(Category $category): JsonResponse
    {
        // 檢查是否有子分類
        if ($category->children()->exists()) {
            return $this->error('此分類有子分類，無法刪除', 422);
        }

        // 檢查是否有商品使用此分類
        if ($category->products()->exists()) {
            return $this->error('此分類有關聯的商品，無法刪除', 422);
        }

        $category->delete();

        return $this->success(null, '分類刪除成功');
    }

    /**
     * 取得子分類
     *
     * @param  Category  $category  父分類
     */
    public function children(Category $category): JsonResponse
    {
        $children = $category->children()
            ->where('status', 'ACTIVE')
            ->orderBy('sort_order', 'asc')
            ->get();

        return $this->success($children, '取得子分類成功');
    }

    /**
     * 取得完整分類樹
     */
    public function tree(): JsonResponse
    {
        $categories = Category::whereNull('parent_id')
            ->where('status', 'ACTIVE')
            ->with(['children' => function ($query) {
                $query->where('status', 'ACTIVE')
                    ->orderBy('sort_order', 'asc')
                    ->with(['children' => function ($q) {
                        $q->where('status', 'ACTIVE')
                            ->orderBy('sort_order', 'asc');
                    }]);
            }])
            ->orderBy('sort_order', 'asc')
            ->get();

        return $this->success($categories, '取得分類樹成功');
    }

    /**
     * 取得所有子孫分類 ID
     */
    private function getDescendantIds(Category $category): array
    {
        $ids = [];
        foreach ($category->children as $child) {
            $ids[] = $child->id;
            $ids = array_merge($ids, $this->getDescendantIds($child));
        }

        return $ids;
    }
}

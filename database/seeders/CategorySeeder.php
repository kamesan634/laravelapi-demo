<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

/**
 * 商品分類 Seeder
 */
class CategorySeeder extends Seeder
{
    public function run(): void
    {
        // 第一層分類
        $electronics = Category::create([
            'code' => 'CAT-ELEC',
            'name' => '3C電子',
            'parent_id' => null,
            'level' => 1,
            'path' => '',
            'sort_order' => 1,
            'icon' => 'laptop',
            'status' => 'ACTIVE',
        ]);

        $fashion = Category::create([
            'code' => 'CAT-FASH',
            'name' => '服飾配件',
            'parent_id' => null,
            'level' => 1,
            'path' => '',
            'sort_order' => 2,
            'icon' => 'shirt',
            'status' => 'ACTIVE',
        ]);

        $food = Category::create([
            'code' => 'CAT-FOOD',
            'name' => '食品飲料',
            'parent_id' => null,
            'level' => 1,
            'path' => '',
            'sort_order' => 3,
            'icon' => 'coffee',
            'status' => 'ACTIVE',
        ]);

        $home = Category::create([
            'code' => 'CAT-HOME',
            'name' => '居家生活',
            'parent_id' => null,
            'level' => 1,
            'path' => '',
            'sort_order' => 4,
            'icon' => 'home',
            'status' => 'ACTIVE',
        ]);

        $beauty = Category::create([
            'code' => 'CAT-BEAU',
            'name' => '美妝保養',
            'parent_id' => null,
            'level' => 1,
            'path' => '',
            'sort_order' => 5,
            'icon' => 'heart',
            'status' => 'ACTIVE',
        ]);

        // 3C電子 - 第二層分類
        $phone = Category::create([
            'code' => 'CAT-PHONE',
            'name' => '手機通訊',
            'parent_id' => $electronics->id,
            'level' => 2,
            'path' => $electronics->id,
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-COMP',
            'name' => '電腦週邊',
            'parent_id' => $electronics->id,
            'level' => 2,
            'path' => $electronics->id,
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-AUDIO',
            'name' => '耳機音響',
            'parent_id' => $electronics->id,
            'level' => 2,
            'path' => $electronics->id,
            'sort_order' => 3,
            'status' => 'ACTIVE',
        ]);

        // 手機通訊 - 第三層分類
        Category::create([
            'code' => 'CAT-IPHONE',
            'name' => 'iPhone',
            'parent_id' => $phone->id,
            'level' => 3,
            'path' => "{$electronics->id}/{$phone->id}",
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-ANDROID',
            'name' => 'Android手機',
            'parent_id' => $phone->id,
            'level' => 3,
            'path' => "{$electronics->id}/{$phone->id}",
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-ACC',
            'name' => '手機配件',
            'parent_id' => $phone->id,
            'level' => 3,
            'path' => "{$electronics->id}/{$phone->id}",
            'sort_order' => 3,
            'status' => 'ACTIVE',
        ]);

        // 服飾配件 - 第二層分類
        Category::create([
            'code' => 'CAT-MEN',
            'name' => '男裝',
            'parent_id' => $fashion->id,
            'level' => 2,
            'path' => $fashion->id,
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-WOMEN',
            'name' => '女裝',
            'parent_id' => $fashion->id,
            'level' => 2,
            'path' => $fashion->id,
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-SHOES',
            'name' => '鞋類',
            'parent_id' => $fashion->id,
            'level' => 2,
            'path' => $fashion->id,
            'sort_order' => 3,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-BAGS',
            'name' => '包包配件',
            'parent_id' => $fashion->id,
            'level' => 2,
            'path' => $fashion->id,
            'sort_order' => 4,
            'status' => 'ACTIVE',
        ]);

        // 食品飲料 - 第二層分類
        Category::create([
            'code' => 'CAT-SNACK',
            'name' => '零食餅乾',
            'parent_id' => $food->id,
            'level' => 2,
            'path' => $food->id,
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-DRINK',
            'name' => '飲料沖泡',
            'parent_id' => $food->id,
            'level' => 2,
            'path' => $food->id,
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-FRESH',
            'name' => '生鮮食品',
            'parent_id' => $food->id,
            'level' => 2,
            'path' => $food->id,
            'sort_order' => 3,
            'status' => 'ACTIVE',
        ]);

        // 居家生活 - 第二層分類
        Category::create([
            'code' => 'CAT-FURN',
            'name' => '傢俱寢具',
            'parent_id' => $home->id,
            'level' => 2,
            'path' => $home->id,
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-KITCH',
            'name' => '廚房用品',
            'parent_id' => $home->id,
            'level' => 2,
            'path' => $home->id,
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-CLEAN',
            'name' => '清潔用品',
            'parent_id' => $home->id,
            'level' => 2,
            'path' => $home->id,
            'sort_order' => 3,
            'status' => 'ACTIVE',
        ]);

        // 美妝保養 - 第二層分類
        Category::create([
            'code' => 'CAT-SKIN',
            'name' => '保養品',
            'parent_id' => $beauty->id,
            'level' => 2,
            'path' => $beauty->id,
            'sort_order' => 1,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-MAKEUP',
            'name' => '彩妝',
            'parent_id' => $beauty->id,
            'level' => 2,
            'path' => $beauty->id,
            'sort_order' => 2,
            'status' => 'ACTIVE',
        ]);

        Category::create([
            'code' => 'CAT-HAIR',
            'name' => '髮品護理',
            'parent_id' => $beauty->id,
            'level' => 2,
            'path' => $beauty->id,
            'sort_order' => 3,
            'status' => 'ACTIVE',
        ]);
    }
}

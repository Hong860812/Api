<?php

namespace App\Features\Cymbio\Repo;

use App\Entities\Product;
use App\Entities\ProductLang;
use Illuminate\Support\Collection;

/**
 * Class ProductLangRepo
 * @package App\Features\Cymbio\Repo
 */
class ProductLangRepo
{

    /** @var ProductLang */
    private $productLang;

    /** @var array */
    private $langIds;

    /**
     * ProductLangRepo constructor.
     * @param ProductLang $productLang
     */
    public function __construct(ProductLang $productLang)
    {
        $this->productLang = $productLang;
        $this->langIds     = (array)config('setting.lang');
    }

    /**
     * 因為 ProductLang 為組合鍵，但 Laravel 沒有支援，<br>
     * 並且在 ProductLangEntity 設定的 primary key 為 id_product，<br>
     * 故這裡無法以 Eloquent 的方式來更新，此為不得已的寫法
     *
     * @param Product $product
     * @param string $description
     * @return Collection
     */
    public function createDescription(Product $product, string $description): Collection
    {
        return collect($this->langIds)
            ->mapWithKeys(function (int $langId) use ($product, $description) {
                return [
                    $product->supplier_reference => $this->productLang->createDescription($product->id_product, $langId, $description),
                ];
            });
    }
}
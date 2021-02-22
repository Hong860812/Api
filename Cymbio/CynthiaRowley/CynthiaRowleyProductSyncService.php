<?php


namespace App\Features\Cymbio\CynthiaRowley;

use App\Features\Cymbio\CymbioProductUpdateService;
use App\Repositories\ProductRepository;
use Illuminate\Support\Collection;

/**
 * Class CynthiaRowleyProductSyncService
 * @package App\Features\Cymbio\CynthiaRowley
 */
class CynthiaRowleyProductSyncService
{
    /** @var ProductRepository */
    protected ProductRepository $productRepository;

    /** @var CynthiaRowleyProductApiService */
    protected CynthiaRowleyProductApiService $apiService;

    /** @var CynthiaRowleyProductCreateService */
    protected CynthiaRowleyProductCreateService $productCreateService;

    /** @var CymbioProductUpdateService */
    protected CymbioProductUpdateService $productUpdater;

    /** @var CynthiaRowleyProductRepo */
    protected CynthiaRowleyProductRepo $cynthiaRowleyProductRepo;

    /** @var CynthiaRowleyProductResetService */
    protected CynthiaRowleyProductResetService $resetService;

    /**
     * 最多一次 1000 筆,所以 給 1000
     * @var int
     */
    const COUNT_MAX = 1000;

    /**
     * CynthiaRowleyProductSyncService constructor.
     * @param CynthiaRowleyProductCreateService $productCreateService
     * @param CymbioProductUpdateService $productUpdater
     * @param CynthiaRowleyProductApiService $cynthiaRowleyProductApiService
     * @param CynthiaRowleyProductRepo $cynthiaRowleyProductRepo
     * @param CynthiaRowleyProductResetService $cynthiaRowleyProductResetService
     */
    public function __construct(
        CynthiaRowleyProductCreateService $productCreateService,
        CymbioProductUpdateService $productUpdater,
        CynthiaRowleyProductApiService $cynthiaRowleyProductApiService,
        CynthiaRowleyProductRepo $cynthiaRowleyProductRepo,
        CynthiaRowleyProductResetService $cynthiaRowleyProductResetService)
    {
        $this->productCreateService = $productCreateService;
        $this->productUpdater = $productUpdater;
        $this->apiService = $cynthiaRowleyProductApiService;
        $this->cynthiaRowleyProductRepo = $cynthiaRowleyProductRepo;
        $this->resetService = $cynthiaRowleyProductResetService;
    }

    /**
     * 同步商品
     * @return array
     */
    public function syncProduct()
    {
        $totalProductsFromApiCounts = 0;
        $totalCreateResultCounts = 0;
        $totalUpdateResultCounts = 0;
        $offset = 0;

        $productsInDb = $this->cynthiaRowleyProductRepo->getProducts();

        do {
            $productsFromApi = $this->apiService->getProductWithOffset($offset);
            $createResult = $this->getCreateResult($productsFromApi, $productsInDb);
            $updateResult = $this->getUpdateResult($productsFromApi, $productsInDb);
            $unavailableProducts = $productsInDb->diffKeys($productsFromApi);

            $totalProductsFromApiCounts += $productsFromApi->count();
            $totalCreateResultCounts += $createResult->count();
            $totalUpdateResultCounts += $updateResult->count();
            $offset += self::COUNT_MAX;
        } while ($productsFromApi->count() !== 0);

        $resetResult = $this->getResetResult($unavailableProducts);

        return [
            '自API取得的商品筆數' => $totalProductsFromApiCounts,
            '新增的商品數' => $totalCreateResultCounts,
            '更新的商品數' => $totalUpdateResultCounts,
            '被重設的筆數' => $resetResult->first(),
        ];
    }

    /**
     * 取得將庫存設定為 0 的商品
     * @param Collection $productsInDb
     * @return Collection
     */
    private function getResetResult(Collection $productsInDb)
    {
        return $this->resetService
            ->setProductsInDb($productsInDb)
            ->updateQty();
    }

    /**
     * 取得建立的結果
     * @param Collection $productsFromApi
     * @param Collection $productsInDb
     * @return Collection
     */
    private function getCreateResult(Collection $productsFromApi, Collection $productsInDb): Collection
    {
        return $this->productCreateService
            ->setProductsFromApi($productsFromApi)
            ->setProductsInDb($productsInDb)
            ->init()
            ->updateQty();
    }

    /**
     * 取得更新的結果
     * @param Collection $productsFromApi
     * @param Collection $productsInDb
     * @return Collection
     */
    private function getUpdateResult(Collection $productsFromApi, Collection $productsInDb): Collection
    {
        return $this->productUpdater
            ->setProductsFromApi($productsFromApi)
            ->setProductsInDb($productsInDb)
            ->init()
            ->updateQty();
    }
}
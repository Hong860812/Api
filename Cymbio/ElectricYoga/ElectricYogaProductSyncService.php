<?php


namespace App\Features\Cymbio\ElectricYoga;

use App\Features\Cymbio\CymbioProductUpdateService;
use App\Repositories\ProductRepository;
use Illuminate\Support\Collection;

/**
 * Class ElectricYogaProductSyncService
 * @package App\Features\Cymbio\ElectricYoga
 */
class ElectricYogaProductSyncService
{
    /** @var ProductRepository */
    protected ProductRepository $productRepository;

    /** @var ElectricYogaProductApiService */
    protected ElectricYogaProductApiService $apiService;

    /** @var ElectricYogaProductCreateService */
    protected ElectricYogaProductCreateService $productCreateService;

    /** @var CymbioProductUpdateService */
    protected CymbioProductUpdateService $productUpdater;

    /** @var ElectricYogaProductRepo */
    protected ElectricYogaProductRepo $electricYogaProductRepo;

    /** @var ElectricYogaProductResetService */
    protected ElectricYogaProductResetService $resetService;

    /**
     * ElectricYogaProductSyncService constructor.
     * @param ElectricYogaProductCreateService $productCreateService
     * @param CymbioProductUpdateService $productUpdater
     * @param ElectricYogaProductApiService $electricYogaProductApiService
     * @param ElectricYogaProductRepo $electricYogaProductRepo
     * @param ElectricYogaProductResetService $electricYogaProductResetService
     */
    public function __construct(
        ElectricYogaProductCreateService $productCreateService,
        CymbioProductUpdateService $productUpdater,
        ElectricYogaProductApiService $electricYogaProductApiService,
        ElectricYogaProductRepo $electricYogaProductRepo,
        ElectricYogaProductResetService $electricYogaProductResetService)
    {
        $this->productCreateService    = $productCreateService;
        $this->productUpdater          = $productUpdater;
        $this->apiService              = $electricYogaProductApiService;
        $this->electricYogaProductRepo = $electricYogaProductRepo;
        $this->resetService            = $electricYogaProductResetService;
    }

    /**
     * 同步商品
     * @return array
     */
    public function syncProduct()
    {
        $productsInDb        = $this->electricYogaProductRepo->getProducts();
        $productsFromApi     = $this->apiService->getProduct();
        $createResult        = $this->getCreateResult($productsFromApi, $productsInDb);
        $updateResult        = $this->getUpdateResult($productsFromApi, $productsInDb);
        $unavailableProducts = $productsInDb->diffKeys($productsFromApi);
        $resetResult         = $this->getResetResult($unavailableProducts);

        $totalProductsFromApiCounts = $productsFromApi->count();
        $totalCreateResultCounts    = $createResult->count();
        $totalUpdateResultCounts    = $updateResult->count();
        return [
            '自API取得的商品筆數' => $totalProductsFromApiCounts,
            '新增的商品數'      => $totalCreateResultCounts,
            '更新的商品數'      => $totalUpdateResultCounts,
            '被重設的筆數'      => $resetResult->first(),
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
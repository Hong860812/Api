<?php


namespace App\Features\Cymbio\SolSana;

use App\Features\Cymbio\CymbioProductUpdateService;
use App\Repositories\ProductRepository;
use Illuminate\Support\Collection;

/**
 * Class SolSanaProductSyncService
 * @package App\Features\Cymbio\SolSana
 */
class SolSanaProductSyncService
{
    /** @var ProductRepository */
    protected $productRepository;

    /** @var SolSanaProductApiService */
    protected $apiService;

    /** @var SolSanaProductCreateService */
    protected $productCreateService;

    /** @var CymbioProductUpdateService */
    protected $productUpdater;

    /** @var SolSanaProductRepo */
    protected $solSanaProductRepo;

    /** @var SolSanaProductResetService */
    protected $resetService;

    /**
     * SolSanaProductSyncService constructor.
     * @param SolSanaProductCreateService $productCreateService
     * @param CymbioProductUpdateService $productUpdater
     * @param SolSanaProductApiService $solSanaProductApiService
     * @param SolSanaProductRepo $solSanaProductRepo
     * @param SolSanaProductResetService $solSanaProductResetService
     */
    public function __construct(
        SolSanaProductCreateService $productCreateService,
        CymbioProductUpdateService $productUpdater,
        SolSanaProductApiService $solSanaProductApiService,
        SolSanaProductRepo $solSanaProductRepo,
        SolSanaProductResetService $solSanaProductResetService)
    {
        $this->productCreateService = $productCreateService;
        $this->productUpdater       = $productUpdater;
        $this->apiService           = $solSanaProductApiService;
        $this->solSanaProductRepo   = $solSanaProductRepo;
        $this->resetService         = $solSanaProductResetService;
    }

    /**
     * @return array
     */
    public function syncProduct()
    {
        $productsInDb        = $this->solSanaProductRepo->getProducts();
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
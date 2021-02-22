<?php


namespace App\Features\Cymbio\FromStXavie;

use App\Features\Cymbio\CymbioProductUpdateService;
use App\Repositories\ProductRepository;
use Illuminate\Support\Collection;

/**
 * Class FromStXavieProductSyncService
 * @package App\Features\Cymbio\FromStXavie
 */
class FromStXavieProductSyncService
{
    /** @var ProductRepository */
    protected $productRepository;

    /** @var FromStXavieProductApiService */
    protected $apiService;

    /** @var FromStXavieProductCreateService */
    protected $productCreateService;

    /** @var CymbioProductUpdateService */
    protected $productUpdater;

    /** @var FromStXavieProductRepo */
    protected $fromStXavieProductRepo;

    /** @var FromStXavieProductResetService */
    protected $resetService;

    /**
     * FromStXavieProductSyncService constructor.
     * @param FromStXavieProductCreateService $productCreateService
     * @param CymbioProductUpdateService $productUpdater
     * @param FromStXavieProductApiService $fromStXavieProductApiService
     * @param FromStXavieProductRepo $fromStXavieProductRepo
     * @param FromStXavieProductResetService $fromStXavieProductResetService
     */
    public function __construct(
        FromStXavieProductCreateService $productCreateService,
        CymbioProductUpdateService $productUpdater,
        FromStXavieProductApiService $fromStXavieProductApiService,
        FromStXavieProductRepo $fromStXavieProductRepo,
        FromStXavieProductResetService $fromStXavieProductResetService)
    {
        $this->productCreateService   = $productCreateService;
        $this->productUpdater         = $productUpdater;
        $this->apiService             = $fromStXavieProductApiService;
        $this->fromStXavieProductRepo = $fromStXavieProductRepo;
        $this->resetService           = $fromStXavieProductResetService;
    }

    /**
     * @return array
     */
    public function syncProduct()
    {
        $productsInDb        = $this->fromStXavieProductRepo->getProducts();
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
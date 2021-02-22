<?php


namespace App\Features\Cymbio\Camper;

use App\Features\Cymbio\CymbioProductUpdateService;
use App\Repositories\ProductRepository;
use Illuminate\Support\Collection;

/**
 * Class CamperProductSyncService
 * @package App\Features\Cymbio\Camper
 */
class CamperProductSyncService
{
    /** @var ProductRepository */
    protected $productRepository;

    /** @var CamperProductApiService */
    protected $apiService;

    /** @var CamperProductCreateService */
    protected $productCreateService;

    /** @var CymbioProductUpdateService */
    protected $productUpdater;

    /** @var CamperProductRepo */
    protected $camperProductRepo;

    /** @var CamperProductResetService */
    protected $resetService;

    /**
     * 最多一次 1000 筆,所以 給 1000
     * @var int
     */
    const COUNT_MAX = 1000;

    /**
     * CamperProductSyncService constructor.
     * @param CamperProductCreateService $productCreateService
     * @param CymbioProductUpdateService $productUpdater
     * @param CamperProductApiService $camperProductApiService
     * @param CamperProductRepo $camperProductRepo
     * @param CamperProductResetService $camperProductResetService
     */
    public function __construct(
        CamperProductCreateService $productCreateService,
        CymbioProductUpdateService $productUpdater,
        CamperProductApiService $camperProductApiService,
        CamperProductRepo $camperProductRepo,
        CamperProductResetService $camperProductResetService)
    {
        $this->productCreateService = $productCreateService;
        $this->productUpdater       = $productUpdater;
        $this->apiService           = $camperProductApiService;
        $this->camperProductRepo    = $camperProductRepo;
        $this->resetService         = $camperProductResetService;
    }

    /**
     * 同步商品
     * @return array
     */
    public function syncProduct()
    {
        $totalProductsFromApiCounts = 0;
        $totalCreateResultCounts    = 0;
        $totalUpdateResultCounts    = 0;
        $offset                     = 0;

        $productsInDb        = $this->camperProductRepo->getProducts();
        $unavailableProducts = $productsInDb;

        do {
            $productsFromApi     = $this->apiService->getProductWithOffset($offset);
            $createResult        = $this->getCreateResult($productsFromApi, $productsInDb);
            $updateResult        = $this->getUpdateResult($productsFromApi, $productsInDb);
            $unavailableProducts = $unavailableProducts->diffKeys($productsFromApi);

            $totalProductsFromApiCounts += $productsFromApi->count();
            $totalCreateResultCounts    += $createResult->count();
            $totalUpdateResultCounts    += $updateResult->count();
            $offset                     += self::COUNT_MAX;
        } while ($productsFromApi->count() !== 0);

        $resetResult = $this->getResetResult($unavailableProducts);

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
<?php

namespace App\Features\Cymbio;

use App\Features\Cymbio\Repo\ProductAttributeRepo;
use App\Features\Cymbio\Repo\ProductLangRepo;
use App\Features\Cymbio\Repo\ProductStockRepo;
use App\Features\Cymbio\Repo\SpecificPriceRepo;
use App\Services\Product\ProductCreateService;
use App\Tools\ImageDownloader;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Class CymbioAbsProductService
 * @package App\Features\Cymbio
 */
abstract class CymbioAbsProductService
{
    /** @var string 回傳結果為成功 */
    const RESULT_SUCCESS = 'success';

    /** @var string 回傳結果為失敗 */
    const RESULT_FAIL = 'fail';

    /** @var ProductCreateService */
    protected $createService;

    /** @var ProductLangRepo */
    protected $productLangRepo;

    /** @var ProductStockRepo */
    protected $productStockRepo;

    /** @var ImageDownloader */
    protected $imageDownloader;

    /** @var ProductAttributeRepo */
    protected $productAttributeRepo;

    /** @var SpecificPriceRepo */
    protected $specificPriceRepo;

    /** @var Collection */
    protected $productsFromApi;

    /** @var Collection */
    protected $productsInDb;

    /** @var Collection */
    protected $productsNeedToCreate;

    /** @var Collection */
    protected $productsNeedToUpdate;

    /**
     * CymbioAbsProductService constructor.
     * CymbioAbsProductService constructor.
     * @param ProductCreateService $createService
     * @param ProductLangRepo $productLangRepo
     * @param ProductStockRepo $productStockRepo
     * @param ImageDownloader $imageDownloader
     * @param ProductAttributeRepo $productAttributeRepo
     * @param SpecificPriceRepo $specificPriceRepo
     */
    public function __construct(ProductCreateService $createService, ProductLangRepo $productLangRepo, ProductStockRepo $productStockRepo, ImageDownloader $imageDownloader, ProductAttributeRepo $productAttributeRepo, SpecificPriceRepo $specificPriceRepo)
    {
        $this->createService        = $createService;
        $this->productLangRepo      = $productLangRepo;
        $this->productStockRepo     = $productStockRepo;
        $this->imageDownloader      = $imageDownloader;
        $this->productAttributeRepo = $productAttributeRepo;
        $this->specificPriceRepo    = $specificPriceRepo;
    }

    /**
     * @param Collection $productsFromApi
     * @return $this
     */
    public function setProductsFromApi(Collection $productsFromApi): self
    {
        $this->productsFromApi = $productsFromApi;
        return $this;
    }

    /**
     * @param Collection $productsInDb
     * @return $this
     */
    public function setProductsInDb(Collection $productsInDb): self
    {
        $this->productsInDb = $productsInDb;
        return $this;
    }

    /**
     * @return $this
     */
    abstract public function init(): self;

    /**
     * @return Collection
     */
    abstract public function updateQty(): Collection;

    /**
     * valid 是否有定義必要的成員變數
     * @throws InvalidArgumentException
     */
    protected function valid(): void
    {
        if (empty($this->productsFromApi)) {
            throw new InvalidArgumentException('沒有定義 productFromApi');
        }

        if (empty($this->productsInDb)) {
            throw new InvalidArgumentException('沒有定義 productsInDb');
        }
    }

}
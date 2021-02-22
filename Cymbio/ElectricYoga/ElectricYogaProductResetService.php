<?php

namespace App\Features\Cymbio\ElectricYoga;

use App\Features\Cymbio\CymbioAbsProductService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * 將架上屬於 Electric Yoga 的商品，予以歸零
 * Class ElectricYogaProductResetService
 * @package App\Features\Cymbio\ElectricYoga
 */
class ElectricYogaProductResetService extends CymbioAbsProductService
{

    /**
     * @return $this
     */
    public function init(): CymbioAbsProductService
    {
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function valid(): void
    {
        if (empty($this->productsInDb)) {
            throw new InvalidArgumentException('沒有定義 productsInDb');
        }
    }

    /**
     * @return Collection
     */
    public function updateQty(): Collection
    {
        $this->valid();
        return collect($this->productStockRepo->resetStockByProducts($this->productsInDb));
    }
}
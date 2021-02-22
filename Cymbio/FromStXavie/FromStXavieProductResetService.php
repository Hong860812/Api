<?php

namespace App\Features\Cymbio\FromStXavie;

use App\Features\Cymbio\CymbioAbsProductService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * 將架上屬於 SolSan 的商品，予以歸零
 * Class FromStXavieProductResetService
 * @package App\Features\Cymbio\FromStXavie
 */
class FromStXavieProductResetService extends CymbioAbsProductService
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
     * @throws InvalidArgumentException
     */
    public function updateQty(): Collection
    {
        $this->valid();
        return collect($this->productStockRepo->resetStockByProducts($this->productsInDb));
    }
}
<?php

namespace App\Features\Cymbio\CynthiaRowley;

use Illuminate\Support\Collection;


/**
 * Class CynthiaRowleyProductRepo
 * @package App\Features\Cymbio\CynthiaRowley
 */
class CynthiaRowleyProductRepo
{
    /** @var CynthiaRowleyProduct */
    private CynthiaRowleyProduct $product;

    /**
     * CynthiaRowleyProductRepo constructor.
     * @param CynthiaRowleyProduct $product
     */
    public function __construct(CynthiaRowleyProduct $product)
    {
        $this->product = $product;
    }

    /**
     * @return Collection
     */
    public function getProducts(): Collection
    {
        return $this->product->getProducts()
            ->keyBy('supplierReference');
    }
}
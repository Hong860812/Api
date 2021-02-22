<?php

namespace App\Features\Cymbio\SolSana;

use Illuminate\Support\Collection;

/**
 * Class SolSanaProductRepo
 * @package App\Features\Cymbio\SolSana
 */
class SolSanaProductRepo
{

    /** @var SolSanaProduct */
    private $product;

    /**
     * SolSanaProductRepo constructor.
     * @param SolSanaProduct $product
     */
    public function __construct(SolSanaProduct $product)
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
<?php

namespace App\Features\Cymbio\ElectricYoga;

use Illuminate\Support\Collection;


/**
 * Class ElectricYogaProductRepo
 * @package App\Features\Cymbio\ElectricYoga
 */
class ElectricYogaProductRepo
{
    /** @var ElectricYogaProduct */
    private $product;

    /**
     * ElectricYogaProductRepo constructor.
     * @param ElectricYogaProduct $product
     */
    public function __construct(ElectricYogaProduct $product)
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
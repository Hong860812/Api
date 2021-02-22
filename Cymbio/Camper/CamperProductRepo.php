<?php

namespace App\Features\Cymbio\Camper;

use Illuminate\Support\Collection;


/**
 * Class CamperProductRepo
 * @package App\Features\Cymbio\Camper
 */
class CamperProductRepo
{
    /** @var CamperProduct */
    private $product;

    /**
     * CamperProductRepo constructor.
     * @param CamperProduct $product
     */
    public function __construct(CamperProduct $product)
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
<?php

namespace App\Features\Cymbio\FromStXavie;

use Illuminate\Support\Collection;

/**
 * Class FromStXavieProductRepo
 * @package App\Features\Cymbio\FromStXavie
 */
class FromStXavieProductRepo
{

    /** @var FromStXavieProduct */
    private $product;

    /**
     * FromStXavieProductRepo constructor.
     * @param FromStXavieProduct $product
     */
    public function __construct(FromStXavieProduct $product)
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
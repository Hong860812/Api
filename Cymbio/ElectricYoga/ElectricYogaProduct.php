<?php

namespace App\Features\Cymbio\ElectricYoga;

use App\Features\Cymbio\CymbioProduct;


/**
 * Class ElectricYogaProduct
 * @package App\Features\Cymbio
 */
class ElectricYogaProduct extends CymbioProduct
{
    /** @var int 品牌的 supplier id */
    protected int $supplierId = ElectricYogaEnum::IFCHIC_SUPPLIER_ID;

    /** @var int 品牌的 brand id */
    protected int $manufacturerId = ElectricYogaEnum::IFCHIC_MANUFACTURER_ID;

    /** @var int season id */
    protected int $seasonId = ElectricYogaEnum::IFCHIC_SEASON_ID;

}
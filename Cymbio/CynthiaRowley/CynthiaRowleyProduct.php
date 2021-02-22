<?php

namespace App\Features\Cymbio\CynthiaRowley;

use App\Features\Cymbio\CymbioProduct;

/**
 * Class CynthiaRowleyProduct
 * @package App\Features\Cymbio\CynthiaRowley
 */
class CynthiaRowleyProduct extends CymbioProduct
{
    /** @var int 品牌的 supplier id */
    protected int $supplierId = CynthiaRowleyEnum::IFCHIC_SUPPLIER_ID;
    /** @var int 品牌的 brand id */
    protected int $manufacturerId = CynthiaRowleyEnum::IFCHIC_MANUFACTURER_ID;
    /** @var int season id */
    protected int $seasonId = CynthiaRowleyEnum::IFCHIC_SEASON_ID;
}
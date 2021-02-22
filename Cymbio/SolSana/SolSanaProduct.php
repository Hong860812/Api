<?php

namespace App\Features\Cymbio\SolSana;

use App\Features\Cymbio\CymbioProduct;

/**
 * Class SolSanaProduct
 * @package App\Features\Cymbio
 */
class SolSanaProduct extends CymbioProduct
{
    /** @var int 品牌的 supplier id */
    protected int $supplierId = SolSanaEnum::IFCHIC_SUPPLIER_ID;

    /** @var int 品牌的 brand id */
    protected int $manufacturerId = SolSanaEnum::IFCHIC_MANUFACTURER_ID;

    /** @var int 品牌的 season id */
    protected int $seasonId = SolSanaEnum::IFCHIC_SEASON_ID;

}
<?php

namespace App\Features\Cymbio\Camper;

use App\Features\Cymbio\CymbioProduct;

/**
 * Class CamperProduct
 * @package App\Features\Cymbio\Camper
 */
class CamperProduct extends CymbioProduct
{
    /** @var int 品牌的 supplier id */
    protected int $supplierId = CamperEnum::IFCHIC_SUPPLIER_ID;
    /** @var int 品牌的 brand id */
    protected int $manufacturerId = CamperEnum::IFCHIC_MANUFACTURER_ID;
    /** @var int season id */
    protected int $seasonId = CamperEnum::IFCHIC_SEASON_ID;
}
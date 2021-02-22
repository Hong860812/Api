<?php

namespace App\Features\Cymbio\FromStXavie;

use App\Features\Cymbio\CymbioProduct;

/**
 * Class FromStXavieProduct
 * @package App\Features\Cymbio
 */
class FromStXavieProduct extends CymbioProduct
{
    /** @var int 品牌的 supplier id */
    protected int $supplierId = FromStXavieEnum::IFCHIC_SUPPLIER_ID;

    /** @var int 品牌的 brand id */
    protected int $manufacturerId = FromStXavieEnum::IFCHIC_MANUFACTURER_ID;

    /** @var int 品牌的 season id */
    protected int $seasonId = FromStXavieEnum::IFCHIC_SEASON_ID;

}
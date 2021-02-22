<?php


namespace App\Features\Cymbio\CynthiaRowley;

use App\Features\Cymbio\CymbioProductCreateService;

/**
 * Class CynthiaRowleyProductCreateService
 * @package App\Features\Cymbio\CynthiaRowley
 */
class CynthiaRowleyProductCreateService extends CymbioProductCreateService
{
    /** @var int Currency Id USD */
    const SUPPLIER_WHOLE_SALE_PRICE_CURRENCY_ID = 1;

    /** @var int 品牌的 supplier id */
    protected int $supplierId = CynthiaRowleyEnum::IFCHIC_SUPPLIER_ID;

    /** @var int 品牌的 brand id */
    protected int $manufacturerId = CynthiaRowleyEnum::IFCHIC_MANUFACTURER_ID;

    /** @var int season id */
    protected int $seasonId = CynthiaRowleyEnum::IFCHIC_SEASON_ID;

}
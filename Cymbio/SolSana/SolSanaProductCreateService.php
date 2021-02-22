<?php


namespace App\Features\Cymbio\SolSana;

use App\Features\Cymbio\CymbioProductCreateService;

/**
 * Class SolSanaProductCreateService
 * @package App\Features\Cymbio\SolSana
 */
class SolSanaProductCreateService extends CymbioProductCreateService
{
    /** @var int Currency Id USD */
    const SUPPLIER_WHOLE_SALE_PRICE_CURRENCY_ID = 1;

    /** @var int 品牌的 supplier id */
    protected $supplierId = SolSanaEnum::IFCHIC_SUPPLIER_ID;

    /** @var int 品牌的 brand id */
    protected $manufacturerId = SolSanaEnum::IFCHIC_MANUFACTURER_ID;

    /** @var int 品牌的 season id */
    protected $seasonId = SolSanaEnum::IFCHIC_SEASON_ID;
}
<?php


namespace App\Features\Cymbio\FromStXavie;

use App\Features\Cymbio\CymbioProductCreateService;

/**
 * Class FromStXavieProductCreateService
 * @package App\Features\Cymbio\FromStXavie
 */
class FromStXavieProductCreateService extends CymbioProductCreateService
{
    /** @var int Currency Id USD */
    const SUPPLIER_WHOLE_SALE_PRICE_CURRENCY_ID = 1;

    /** @var int 品牌的 supplier id */
    protected $supplierId = FromStXavieEnum::IFCHIC_SUPPLIER_ID;

    /** @var int 品牌的 brand id */
    protected $manufacturerId = FromStXavieEnum::IFCHIC_MANUFACTURER_ID;

    /** @var int 品牌的 season id */
    protected $seasonId = FromStXavieEnum::IFCHIC_SEASON_ID;
}
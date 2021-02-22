<?php


namespace App\Features\Cymbio\ElectricYoga;

use App\Features\Cymbio\CymbioProductCreateService;

/**
 * Class ElectricYogaProductCreateService
 * @package App\Features\Cymbio\ElectricYoga
 */
class ElectricYogaProductCreateService extends CymbioProductCreateService
{
    /** @var int Currency Id USD */
    const SUPPLIER_WHOLE_SALE_PRICE_CURRENCY_ID = 1;

    /** @var int 品牌的 supplier id */
    protected $supplierId = ElectricYogaEnum::IFCHIC_SUPPLIER_ID;

    /** @var int 品牌的 brand id */
    protected $manufacturerId = ElectricYogaEnum::IFCHIC_MANUFACTURER_ID;

    /** @var int season id */
    protected $seasonId = ElectricYogaEnum::IFCHIC_SEASON_ID;

}
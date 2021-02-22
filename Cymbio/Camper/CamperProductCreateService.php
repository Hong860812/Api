<?php


namespace App\Features\Cymbio\Camper;

use App\Features\Cymbio\CymbioProductCreateService;

/**
 * Class CamperProductCreateService
 * @package App\Features\Cymbio\Camper
 */
class CamperProductCreateService extends CymbioProductCreateService
{
    /** @var int Currency Id USD */
    const SUPPLIER_WHOLE_SALE_PRICE_CURRENCY_ID = 1;

    /** @var int 品牌的 supplier id */
    protected $supplierId = CamperEnum::IFCHIC_SUPPLIER_ID;

    /** @var int 品牌的 brand id */
    protected $manufacturerId = CamperEnum::IFCHIC_MANUFACTURER_ID;

    /** @var int season id */
    protected $seasonId = CamperEnum::IFCHIC_SEASON_ID;

}
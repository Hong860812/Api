<?php

namespace App\Features\Cymbio\Camper;

use BenSampo\Enum\Enum;

/**
 * Class CamperEnum
 * @package App\Features\Cymbio\Camper
 */
class CamperEnum extends Enum
{
    /** @var int 品牌的 supplier id */
    const IFCHIC_SUPPLIER_ID = 227;

    /** @var int 品牌的 brand id */
    const IFCHIC_MANUFACTURER_ID = 377;

    /** @var int 品牌的 season id */
    const IFCHIC_SEASON_ID = 57;
}
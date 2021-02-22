<?php

namespace App\Features\Cymbio\Dto;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * Class CymbioSpecialPriceDto
 * @package App\Features\Cymbio\Dto
 */
class CymbioSpecialPriceDto extends DataTransferObject
{
    /** @var double */
    public $salePrice;

    /** @var \Carbon\Carbon */
    public $start;

    /** @var \Carbon\Carbon */
    public $end;
}
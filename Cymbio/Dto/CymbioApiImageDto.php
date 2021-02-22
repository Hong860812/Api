<?php

namespace App\Features\Cymbio\Dto;

use Spatie\DataTransferObject\DataTransferObject;

/**
 * Cymbio Image 物件
 * Class CymbioApiImageDto
 * @package App\Features\Cymbio\Dto
 */
class CymbioApiImageDto extends DataTransferObject
{
    /** @var string */
    public $id;

    /** @var string */
    public $src;
}
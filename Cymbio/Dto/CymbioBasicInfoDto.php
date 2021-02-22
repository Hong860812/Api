<?php


namespace App\Features\Cymbio\Dto;


use Spatie\DataTransferObject\DataTransferObject;

/**
 * Class CymbioBasicInfoDto
 * @package App\Features\Cymbio\Dto
 */
class CymbioBasicInfoDto extends DataTransferObject
{
    /** @var string */
    public $reference;

    /** @var string */
    public $name;

    /** @var string */
    public $description;

    /** @var string */
    public $detail;

    /** @var \Illuminate\Support\Collection */
    public $imagesUrl;

    /** @var float */
    public $retailPrice;

    /** @var string */
    public $color;

    /** @var int */
    public $categoryId;

    /** @var \App\Features\Cymbio\Dto\CymbioSpecialPriceDto|null */
    public $specificInfo;

    /** @var float */
    public $wholesalePrice;

    /** @var int */
    public $totalInventory;

    /** @var \Illuminate\Support\Collection */
    public $sizeInventory;

    /** @var \Illuminate\Support\Collection */
    public $sizeIds;

    /** @var \Illuminate\Support\Collection */
    public $attributesReference;

    /** @var boolean */
    public $isOneSize;

}

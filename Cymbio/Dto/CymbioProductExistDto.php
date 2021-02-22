<?php

namespace App\Features\Cymbio\Dto;

use Spatie\DataTransferObject\DataTransferObject;
use Spatie\DataTransferObject\DataTransferObjectError;
use UnexpectedValueException;

/**
 * Class CymbioProductExistDto
 * @package App\Features\Cymbio\Dto
 */
class CymbioProductExistDto extends DataTransferObject
{

    /** @var int|null */
    public $idProduct;

    /** @var int */
    public $idManufacturer;

    /** @var string|null */
    public $ean13;

    /** @var string|null */
    public $upc;

    /** @var float|null */
    public $ecotax;

    /** @var int */
    public $quantity;

    /** @var float */
    public $price;

    /** @var float */
    public $supplierRetail;

    /** @var float */
    public $wholesalePrice;

    /** @var string|null */
    public $unity;

    /** @var string */
    public $reference;

    /** @var string */
    public $supplierReference;

    /** @var string|null */
    public $location;

    /** @var float */
    public $width;

    /** @var float */
    public $height;

    /** @var float */
    public $depth;

    /** @var float */
    public $weight;

    /** @var bool */
    public $customizable;

    /** @var bool */
    public $active;

    /** @var string|null */
    public $availableDate;

    /** @var string */
    public $condition;

    /** @var bool */
    public $indexed;

    /** @var string */
    public $visibility;

    /** @var bool */
    public $isDeleted;

    /** @var string */
    public $type;

}
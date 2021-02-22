<?php

namespace App\Features\CymbioOrder\Create\Enum;

use BenSampo\Enum\Enum;

/**
 * Class ShopifyOrderTypeEnum
 * @package App\Features\Shopify\Enum
 */
class CymbioOrderTypeEnum extends Enum
{
    /** @var string */
    const TYPE_TAIWAN = 'Taiwan';

    /** @var string */
    const TYPE_USA = 'USA';

    /** @var string */
    const TYPE_INTERNATIONAL = 'International';
}
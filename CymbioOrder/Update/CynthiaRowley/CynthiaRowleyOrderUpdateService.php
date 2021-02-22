<?php


namespace App\Features\CymbioOrder\Update\CynthiaRowley;


use App\Features\CymbioOrder\Update\AbsCymbioOrderUpdateService;

/**
 * Class CynthiaRowleyOrderUpdateService
 * @package App\Features\CymbioOrder\Update\Camper
 */
class CynthiaRowleyOrderUpdateService extends AbsCymbioOrderUpdateService
{
    /** @var int */
    const SUPPLIER_ID = 223;

    /** @var string */
    const VENDOR_NAME = 'CynthiaRowley';

    /**
     * @return $this
     */
    public function init(): AbsCymbioOrderUpdateService
    {
        $this->supplierId = self::SUPPLIER_ID;
        $this->retailerId = config('cymbio.' . env('APP_ENV') . '.' . self::VENDOR_NAME . '.RETAILER_ID');

        return $this;
    }
}
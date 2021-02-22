<?php


namespace App\Features\CymbioOrder\Update\ElectricYoga;


use App\Features\CymbioOrder\Update\AbsCymbioOrderUpdateService;

/**
 * Class ElectricYogaOrderUpdateService
 * @package App\Features\CymbioOrder\Update\ElectricYoga
 */
class ElectricYogaOrderUpdateService extends AbsCymbioOrderUpdateService
{
    /** @var int */
    const SUPPLIER_ID = 232;

    /** @var string */
    const VENDOR_NAME = 'ElectricYoga';

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
<?php


namespace App\Features\CymbioOrder\Create\ElectricYoga;


use App\Features\CymbioOrder\Create\AbsCymbioOrderService;


/**
 * Class ElectricYogaOrderUpdateService
 * @package App\Features\CymbioOrder\Update\ElectricYoga
 */
class ElectricYogaOrderService extends AbsCymbioOrderService
{
    /** @var int */
    const SUPPLIER_ID = 232;

    /** @var string */
    const VENDOR_NAME = 'ElectricYoga';

    /**
     * @return $this
     */
    public function init(): AbsCymbioOrderService
    {
        $this->supplierId = self::SUPPLIER_ID;
        $this->retailerId = config('cymbio.' . env('APP_ENV') . '.' . self::VENDOR_NAME . '.RETAILER_ID');
        $this->vendorSupplierId = config('cymbio.' . env('APP_ENV') . '.' . self::VENDOR_NAME . '.SUPPLIER_ID');
        $this->vendorName = self::VENDOR_NAME;

        return $this;
    }

}
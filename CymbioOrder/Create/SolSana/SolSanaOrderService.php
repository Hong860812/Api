<?php


namespace App\Features\CymbioOrder\Create\SolSana;


use App\Features\CymbioOrder\Create\AbsCymbioOrderService;

/**
 * Class SolSanaOrderUpdateService
 * @package App\Features\CymbioOrder\Update\SolSana
 */
class SolSanaOrderService extends AbsCymbioOrderService
{
    /** @var int */
    const SUPPLIER_ID = 231;

    /** @var string */
    const VENDOR_NAME = 'SolSana';

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
<?php


namespace App\Features\CymbioOrder\Create\FromStXavie;


use App\Features\CymbioOrder\Create\AbsCymbioOrderService;


/**
 * Class FromStXavieOrderUpdateService
 * @package App\Features\CymbioOrder\Create\FromStXavie
 */
class FromStXavieOrderService extends AbsCymbioOrderService
{
    /** @var int */
    const SUPPLIER_ID = 237;

    /** @var string */
    const VENDOR_NAME = 'FromStXavie';

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
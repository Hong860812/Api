<?php


namespace App\Features\CymbioOrder\Create\Camper;


use App\Features\CymbioOrder\Create\AbsCymbioOrderService;

/**
 * Class CamperOrderUpdateService
 * @package App\Features\CymbioOrder\Update\Camper
 */
class CamperOrderService extends AbsCymbioOrderService
{
    /** @var int */
    const SUPPLIER_ID = 227;

    /** @var string */
    const VENDOR_NAME = 'Camper';

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
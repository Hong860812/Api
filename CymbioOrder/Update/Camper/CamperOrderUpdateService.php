<?php


namespace App\Features\CymbioOrder\Update\Camper;


use App\Features\CymbioOrder\Update\AbsCymbioOrderUpdateService;

/**
 * Class CamperOrderUpdateService
 * @package App\Features\CymbioOrder\Update\Camper
 */
class CamperOrderUpdateService extends AbsCymbioOrderUpdateService
{
    /** @var int */
    const SUPPLIER_ID = 227;

    /** @var string */
    const VENDOR_NAME = 'Camper';

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
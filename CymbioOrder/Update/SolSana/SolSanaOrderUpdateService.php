<?php


namespace App\Features\CymbioOrder\Update\SolSana;


use App\Features\CymbioOrder\Update\AbsCymbioOrderUpdateService;

/**
 * Class SolSanaOrderUpdateService
 * @package App\Features\CymbioOrder\Update\SolSana
 */
class SolSanaOrderUpdateService extends AbsCymbioOrderUpdateService
{
    /** @var int */
    const SUPPLIER_ID = 231;

    /** @var string */
    const VENDOR_NAME = 'SolSana';

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
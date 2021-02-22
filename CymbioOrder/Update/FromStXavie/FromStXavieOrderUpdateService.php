<?php


namespace App\Features\CymbioOrder\Update\FromStXavie;


use App\Features\CymbioOrder\Update\AbsCymbioOrderUpdateService;

/**
 * Class FromStXavieOrderUpdateService
 * @package App\Features\CymbioOrder\Update\FromStXavie
 */
class FromStXavieOrderUpdateService extends AbsCymbioOrderUpdateService
{
    /** @var int */
    const SUPPLIER_ID = 237;

    /** @var string */
    const VENDOR_NAME = 'FromStXavie';

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
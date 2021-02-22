<?php


namespace App\Features\CymbioOrder\Update;

use App\Features\CymbioOrder\CymbioOrderTokenService;
use App\Features\CymbioOrder\Enum\CymbioVendorEnum;
use App\Features\CymbioOrder\Update\Camper\CamperOrderUpdateService;
use App\Features\CymbioOrder\Update\CynthiaRowley\CynthiaRowleyOrderUpdateService;
use App\Features\CymbioOrder\Update\ElectricYoga\ElectricYogaOrderUpdateService;
use App\Features\CymbioOrder\Update\FromStXavie\FromStXavieOrderUpdateService;
use App\Features\CymbioOrder\Update\SolSana\SolSanaOrderUpdateService;
use GuzzleHttp\Client;
use UnexpectedValueException;

/**
 * Class CymbioUpdateServiceDispatcher
 * @package App\Features\CymbioOrder\Update
 */
class CymbioUpdateServiceDispatcher
{
    /** @var Client */
    protected Client $client;

    /** @var CymbioOrderTokenService */
    private CymbioOrderTokenService $cymbioOrderTokenService;

    /**
     * CymbioUpdateServiceDispatcher constructor.
     * @param Client $client
     * @param CymbioOrderTokenService $cymbioOrderTokenService
     */
    public function __construct(Client $client, CymbioOrderTokenService $cymbioOrderTokenService)
    {
        $this->client = $client;
        $this->cymbioOrderTokenService = $cymbioOrderTokenService;
    }


    /**
     * @param int $supplierId
     * @return AbsCymbioOrderUpdateService
     * @throw UnexpectedValueException
     */
    public function getServiceBySupplierId(int $supplierId): AbsCymbioOrderUpdateService
    {
        switch (true) {
            case $supplierId === CymbioVendorEnum::CYNTHIA_ROWLEY_ID:
                return new CynthiaRowleyOrderUpdateService($this->client, $this->cymbioOrderTokenService);
            case $supplierId === CymbioVendorEnum::CAMPER_ID:
                return new CamperOrderUpdateService($this->client, $this->cymbioOrderTokenService);
            case $supplierId === CymbioVendorEnum::SOL_SANA_ID:
                return new SolSanaOrderUpdateService($this->client, $this->cymbioOrderTokenService);
            case $supplierId === CymbioVendorEnum::ELECTRIC_YOGA_ID:
                return new ElectricYogaOrderUpdateService($this->client, $this->cymbioOrderTokenService);
            case $supplierId === CymbioVendorEnum::FROM_ST_XAVIER_ID:
                return new FromStXavieOrderUpdateService($this->client, $this->cymbioOrderTokenService);
            default:
                throw new UnexpectedValueException('沒有對應的supplier update service');
        }
    }
}
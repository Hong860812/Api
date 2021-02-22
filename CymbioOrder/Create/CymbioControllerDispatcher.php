<?php


namespace App\Features\CymbioOrder\Create;

use App\Entities\OrderIdentity;
use App\Features\CymbioOrder\CymbioOrderTokenService;
use App\Features\CymbioOrder\Enum\CymbioVendorEnum;
use App\Features\CymbioOrder\Create\Camper\CamperOrderService;
use App\Features\CymbioOrder\Create\CynthiaRowley\CynthiaRowleyOrderService;
use App\Features\CymbioOrder\Create\ElectricYoga\ElectricYogaOrderService;
use App\Features\CymbioOrder\Create\FromStXavie\FromStXavieOrderService;
use App\Features\CymbioOrder\Create\SolSana\SolSanaOrderService;
use App\Repositories\ThirdPartySupplierRepository;
use App\Transformers\AddressTransformer;
use GuzzleHttp\Client;
use UnexpectedValueException;

/**
 * Class CymbioUpdateServiceDispatcher
 * @package App\Features\CymbioOrder\Update
 */
class CymbioControllerDispatcher
{
    /** @var Client */
    protected Client $client;

    /** @var ThirdPartySupplierRepository */
    protected ThirdPartySupplierRepository $thirdPartySupplierRepo;

    /** @var OrderIdentity */
    protected OrderIdentity $orderIdentity;

    /** @var AddressTransformer */
    protected AddressTransformer $addressTransformer;

    /** @var CymbioOrderTokenService */
    protected CymbioOrderTokenService $cymbioOrderTokenService;

    /**
     * CymbioControllerDispatcher constructor.
     * @param Client $client
     * @param ThirdPartySupplierRepository $thirdPartySupplierRepo
     * @param OrderIdentity $orderIdentity
     * @param AddressTransformer $addressTransformer
     * @param CymbioOrderTokenService $cymbioOrderTokenService
     */
    public function __construct(Client $client, ThirdPartySupplierRepository $thirdPartySupplierRepo, OrderIdentity $orderIdentity, AddressTransformer $addressTransformer, CymbioOrderTokenService $cymbioOrderTokenService)
    {
        $this->client = $client;
        $this->thirdPartySupplierRepo = $thirdPartySupplierRepo;
        $this->orderIdentity = $orderIdentity;
        $this->addressTransformer = $addressTransformer;
        $this->cymbioOrderTokenService = $cymbioOrderTokenService;
    }

    /**
     * @param int $supplierId
     * @return AbsCymbioOrderService
     * @throw UnexpectedValueException
     */
    public function getServiceBySupplierId(int $supplierId): AbsCymbioOrderService
    {
        switch (true) {
            case $supplierId === (new CymbioVendorEnum(CymbioVendorEnum::CYNTHIA_ROWLEY_ID))->value:
                return new CynthiaRowleyOrderService($this->client, $this->thirdPartySupplierRepo, $this->addressTransformer, $this->cymbioOrderTokenService);
            case $supplierId === (new CymbioVendorEnum(CymbioVendorEnum::CAMPER_ID))->value:
                return new CamperOrderService($this->client, $this->thirdPartySupplierRepo, $this->addressTransformer, $this->cymbioOrderTokenService);
            case $supplierId === (new CymbioVendorEnum(CymbioVendorEnum::SOL_SANA_ID))->value:
                return new SolSanaOrderService($this->client, $this->thirdPartySupplierRepo, $this->addressTransformer, $this->cymbioOrderTokenService);
            case $supplierId === (new CymbioVendorEnum(CymbioVendorEnum::ELECTRIC_YOGA_ID))->value:
                return new ElectricYogaOrderService($this->client, $this->thirdPartySupplierRepo, $this->addressTransformer, $this->cymbioOrderTokenService);
            case $supplierId === (new CymbioVendorEnum(CymbioVendorEnum::FROM_ST_XAVIER_ID))->value:
                return new FromStXavieOrderService($this->client, $this->thirdPartySupplierRepo, $this->addressTransformer, $this->cymbioOrderTokenService);
            default:
                throw new UnexpectedValueException('沒有對應的supplier update service');
        }
    }

}
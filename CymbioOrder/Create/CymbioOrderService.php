<?php


namespace App\Features\CymbioOrder\Create;

use App\Entities\OrderDetail;
use App\Repositories\ThirdPartySupplierRepository;
use App\Repositories\VendorOrderRepository;

/**
 * Class CymbioOrderUpdateService
 * @package App\Features\CymbioOrder\Update
 */
class CymbioOrderService
{
    /** @var VendorOrderRepository */
    protected VendorOrderRepository $vendorOrderRepository;

    /** @var AbsCymbioOrderService */
    protected AbsCymbioOrderService $createService;

    /** @var ThirdPartySupplierRepository */
    protected ThirdPartySupplierRepository $thirdPartySupplierRepo;

    /**
     * CymbioOrderService constructor.
     * @param AbsCymbioOrderService $createService
     * @param VendorOrderRepository $vendorOrderRepository
     * @param ThirdPartySupplierRepository $thirdPartySupplierRepo
     */
    public function __construct(
        AbsCymbioOrderService $createService,
        VendorOrderRepository $vendorOrderRepository,
        ThirdPartySupplierRepository $thirdPartySupplierRepo)
    {
        $this->vendorOrderRepository  = $vendorOrderRepository;
        $this->createService          = $createService;
        $this->thirdPartySupplierRepo = $thirdPartySupplierRepo;
    }

    /**
     * @param OrderDetail $orderDetail
     */
    public function createOrder(OrderDetail $orderDetail)
    {

        $response = $this->createService
            ->init()
            ->setOrder($orderDetail->order()->first())
            ->setOrderDetail($orderDetail)
            ->setProduct()
            ->setSupplierShippingInfo()
            ->setCustomerToShippingAddress()
            ->sendRequest();

        if (!is_null($response)) {
            $supplierInfo = $orderDetail->product()->first()->supplier()->first();
            $this->vendorOrderRepository->createVendorOrder($response['id'], $supplierInfo->id_supplier, [$orderDetail->id_order_detail], $orderDetail->id_order);
        }
    }
}
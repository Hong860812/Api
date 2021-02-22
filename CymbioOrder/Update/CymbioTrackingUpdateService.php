<?php


namespace App\Features\CymbioOrder\Update;


use App\Entities\VendorOrder;
use App\Repositories\ConsignmentShipmentRepository;
use App\Repositories\VendorOrderDetailRepository;
use App\Repositories\VendorOrderRepository;
use App\Tools\ReferenceGenerator;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use UnexpectedValueException;

/**
 * Class CymbioTrackingUpdateService
 * @package App\Features\CymbioOrder\Update
 */
class CymbioTrackingUpdateService
{
    /** @var AbsCymbioOrderUpdateService */
    protected AbsCymbioOrderUpdateService $orderUpdateService;

    /** @var VendorOrderRepository */
    protected VendorOrderRepository $vendorOrderRepo;

    /** @var VendorOrderDetailRepository */
    protected VendorOrderDetailRepository $vendorOrderDetailRepo;

    /** @var ConsignmentShipmentRepository */
    protected ConsignmentShipmentRepository $cgmShipmentRepo;

    /** @var ReferenceGenerator */
    protected ReferenceGenerator $referenceGenerator;

    /**
     * CymbioTrackingUpdateService constructor.
     * @param AbsCymbioOrderUpdateService $orderUpdateService
     * @param VendorOrderRepository $vendorOrderRepo
     * @param VendorOrderDetailRepository $vendorOrderDetailRepo
     * @param ConsignmentShipmentRepository $cgmShipmentRepo
     * @param ReferenceGenerator $referenceGenerator
     */
    public function __construct(
        AbsCymbioOrderUpdateService $orderUpdateService,
        VendorOrderRepository $vendorOrderRepo,
        VendorOrderDetailRepository $vendorOrderDetailRepo,
        ConsignmentShipmentRepository $cgmShipmentRepo,
        ReferenceGenerator $referenceGenerator)
    {
        $this->orderUpdateService = $orderUpdateService;
        $this->vendorOrderRepo = $vendorOrderRepo;
        $this->vendorOrderDetailRepo = $vendorOrderDetailRepo;
        $this->cgmShipmentRepo = $cgmShipmentRepo;
        $this->referenceGenerator = $referenceGenerator;
    }

    /**
     * @param VendorOrder $vendorOrder
     */
    public function updateOrderTracking(VendorOrder $vendorOrder): void
    {
        $fulfillmentsInfo = $this->orderUpdateService
            ->init()
            ->getfulfillmentsInfo($vendorOrder->vendor_order_reference);

        $this->updateInfo($fulfillmentsInfo, $vendorOrder);
    }

    /**
     * @param Collection $fulfillmentsInfo
     * @param VendorOrder $vendorOrder
     */
    private function updateInfo(Collection $fulfillmentsInfo, VendorOrder $vendorOrder): void
    {
        if ($fulfillmentsInfo->isEmpty()) {
            throw new UnexpectedValueException('找不到 fulfillments 資訊');
        }

        $lastFulfillmentsInfoRecord = collect($fulfillmentsInfo->last());
        $trackingNumber = $lastFulfillmentsInfoRecord->get('tracking_number');
        if (empty($trackingNumber)) {
            throw new UnexpectedValueException('沒有 Tracking Number 可以更新');
        }

        $this->updateTrackingNumber($lastFulfillmentsInfoRecord, $vendorOrder);
    }

    /**
     * @param Collection $fulfillmentInfo
     * @param VendorOrder $vendorOrder
     */
    public function updateTrackingNumber(Collection $fulfillmentInfo, VendorOrder $vendorOrder)
    {
        $trackingNumber = $fulfillmentInfo->get('tracking_number');

        $shippingDate = Carbon::parse($fulfillmentInfo->get('created_at'));
        $vendorOrder->update([
            'tracking_number' => $trackingNumber,
        ]);
        $orderDetails = $this->vendorOrderDetailRepo->skipPresenter(true)->findWhere([
            'id_vendor_orders' => $vendorOrder->id,
        ]);

        $this->vendorOrderRepo->createConsignmentShipment(
            $vendorOrder->vendor_order_reference,
            $vendorOrder->id_supplier,
            $orderDetails->toArray(),
            $shippingDate,
            $trackingNumber,
            $this->cgmShipmentRepo,
            $this->referenceGenerator
        );
        $this->vendorOrderRepo->createSendTrackingUpdateEmail($orderDetails, $vendorOrder->id_order, $trackingNumber, 'Cymbio Order Tracking Updated');
    }


}
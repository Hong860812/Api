<?php


namespace App\Features\CymbioOrder\Update;


use App\Entities\VendorOrder;
use App\Repositories\ConsignmentShipmentRepository;
use App\Repositories\ThirdPartySupplierRepository;
use App\Repositories\VendorOrderDetailRepository;
use App\Repositories\VendorOrderRepository;
use App\Tools\ReferenceGenerator;
use UnexpectedValueException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Class CymbioOrderUpdateService
 * @package App\Features\CymbioOrder\Update
 */
class CymbioOrderUpdateService
{
    /** @var CymbioUpdateServiceDispatcher */
    protected CymbioUpdateServiceDispatcher $serviceDispatcher;

    /** @var VendorOrderRepository */
    protected VendorOrderRepository $vendorOrderRepo;

    /** @var VendorOrderDetailRepository */
    protected VendorOrderDetailRepository $vendorOrderDetailRepo;

    /** @var ConsignmentShipmentRepository */
    protected ConsignmentShipmentRepository $cgmShipmentRepo;

    /** @var ReferenceGenerator */
    protected ReferenceGenerator $referenceGenerator;

    /** @var ThirdPartySupplierRepository */
    private ThirdPartySupplierRepository $thirdPartySupplierRepo;

    /**
     * CymbioOrderUpdateService constructor.
     * @param CymbioUpdateServiceDispatcher $serviceDispatcher
     * @param VendorOrderRepository $vendorOrderRepo
     * @param VendorOrderDetailRepository $vendorOrderDetailRepo
     * @param ConsignmentShipmentRepository $cgmShipmentRepo
     * @param ReferenceGenerator $referenceGenerator
     * @param ThirdPartySupplierRepository $thirdPartySupplierRepo
     */
    public function __construct(
        CymbioUpdateServiceDispatcher $serviceDispatcher,
        VendorOrderRepository $vendorOrderRepo,
        VendorOrderDetailRepository $vendorOrderDetailRepo,
        ConsignmentShipmentRepository $cgmShipmentRepo,
        ReferenceGenerator $referenceGenerator,
        ThirdPartySupplierRepository $thirdPartySupplierRepo)
    {
        $this->serviceDispatcher      = $serviceDispatcher;
        $this->vendorOrderRepo        = $vendorOrderRepo;
        $this->vendorOrderDetailRepo  = $vendorOrderDetailRepo;
        $this->cgmShipmentRepo        = $cgmShipmentRepo;
        $this->referenceGenerator     = $referenceGenerator;
        $this->thirdPartySupplierRepo = $thirdPartySupplierRepo;
    }


    public function updateOrder(): void
    {
        $supplierIds                 = $this->getSupplierIds();
        $vendorOrdersWithoutTracking = $this->getEmptyTrackingOrders($supplierIds);

        $vendorOrdersWithoutTracking->each(function (VendorOrder $vendorOrder) {
            try {
                if (empty($vendorOrder->vendor_order_reference)) {
                    return;
                }

                DB::beginTransaction();

                $updateService = $this->serviceDispatcher->getServiceBySupplierId($vendorOrder->id_supplier);
                $trackingUpdateService = new CymbioTrackingUpdateService(
                    $updateService,
                    $this->vendorOrderRepo,
                    $this->vendorOrderDetailRepo,
                    $this->cgmShipmentRepo,
                    $this->referenceGenerator
                );
                $trackingUpdateService->updateOrderTracking($vendorOrder);

                DB::commit();
            } catch (UnexpectedValueException $exception) {
                Log::channel('daily_consign')->debug($exception->getMessage(), [
                    'idOrder' => $vendorOrder->id_order,
                    'idVendorOrder' => $vendorOrder->id,
                ]);
                DB::rollback();
            }

        });

    }

    /**
     * @return array
     */
    private function getSupplierIds(): array
    {
        return $this->thirdPartySupplierRepo
            ->getSupplierByType('cymbio')
            ->pluck('id_supplier')
            ->toArray();
    }

    /**
     * @param array $supplierIds
     * @return Collection
     */
    private function getEmptyTrackingOrders(array $supplierIds): Collection
    {
        return collect(
            $this->vendorOrderRepo
                ->skipPresenter(true)
                ->where('tracking_number', '=', '')
                ->whereIn('id_supplier', $supplierIds)
                ->get()
        );
    }
}
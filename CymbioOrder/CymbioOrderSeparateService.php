<?php

namespace App\Features\CymbioOrder;

use App\Repositories\OrderRepository;
use App\Repositories\ThirdPartySupplierRepository;
use Illuminate\Support\Collection;

/**
 * Class CymbioOrderSeparateService
 * @package App\Features\CymbioOrder
 */
class CymbioOrderSeparateService
{
    /** @var OrderRepository */
    protected $orderRepository;

    /** @var ThirdPartySupplierRepository */
    protected $thirdPartySupplierRepo;

    /**
     * CymbioOrderSeparateService constructor.
     * @param OrderRepository $orderRepo
     * @param ThirdPartySupplierRepository $thirdPartySupplierRepo
     */
    public function __construct(OrderRepository $orderRepo, ThirdPartySupplierRepository $thirdPartySupplierRepo)
    {
        $this->orderRepository        = $orderRepo;
        $this->thirdPartySupplierRepo = $thirdPartySupplierRepo;
    }

    /**
     * @param $orderId
     * @return Collection
     */
    public function getCymbioOrderDetail($orderId): Collection
    {

        $getThirdSupplierByType = $this->thirdPartySupplierRepo->getSupplierByType('cymbio');
        $cymbioSupplierIds     = $getThirdSupplierByType->pluck('id_supplier')->toArray();
        $order = $this->orderRepository->skipPresenter(true)->find($orderId);

        return $order->orderDetail()
            ->whereHas('product', function ($query) use ($cymbioSupplierIds) {
                $query->whereIn('id_supplier', $cymbioSupplierIds)->where('type', '<>', 'ifchic');
            })
            ->get();
    }
}
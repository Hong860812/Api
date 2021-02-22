<?php


namespace App\Features\Cymbio;


use App\Entities\Product;
use App\Features\Cymbio\Dto\CymbioProductExistDto;
use Illuminate\Support\Collection;

/**
 * Class CymbioProduct
 * @package App\Features\Cymbio
 */
class CymbioProduct extends Product
{
    /** @var int 品牌的 supplier id */
    protected int $supplierId;

    /** @var int 品牌的 brand id */
    protected int $manufacturerId;

    /** @var int 品牌的 season id */
    protected int $seasonId;

    /** @var int */
    const IS_DELETED = false;


    /**
     * @return Collection
     */
    public function getProducts(): Collection
    {
        $result = $this
            ->where([
                ['id_supplier', '=', $this->supplierId],
                ['id_manufacturer', '=', $this->manufacturerId],
                ['ifchic_id_season', '=', $this->seasonId],
                ['is_deleted', '=', self::IS_DELETED],
            ])
            ->get();
        return collect($result)
            ->map(function (Product $item) {
                return new CymbioProductExistDto([
                    'idProduct'         => $item['id_product'],
                    'idManufacturer'    => $item['id_manufacturer'],
                    'ean13'             => $item['ean13'],
                    'upc'               => $item['upc'],
                    'ecotax'            => (double)$item['ecotax'],
                    'quantity'          => $item['quantity'],
                    'price'             => (double)$item['price'],
                    'supplierRetail'    => (double)$item['supplier_retail'],
                    'wholesalePrice'    => (double)$item['wholesale_price'],
                    'unity'             => $item['unity'],
                    'reference'         => $item['reference'],
                    'supplierReference' => $item['supplier_reference'],
                    'location'          => $item['location'],
                    'width'             => (double)$item['width'],
                    'height'            => (double)$item['height'],
                    'depth'             => (double)$item['depth'],
                    'weight'            => (double)$item['weight'],
                    'customizable'      => (bool)$item['customizable'],
                    'active'            => (bool)$item['active'],
                    'availableDate'     => $item['available_date'],
                    'condition'         => $item['condition'],
                    'indexed'           => (bool)$item['indexed'],
                    'visibility'        => $item['visibility'],
                    'isDeleted'         => (bool)$item['is_deleted'],
                    'type'              => $item['type'],
                ]);
            });
    }
}
<?php

namespace App\Features\Cymbio\Repo;

use App\Entities\Product;
use App\Entities\ProductAttribute;
use App\Entities\ProductAttributeCombination;
use App\Entities\ProductShop;
use App\Entities\StockAvailable;
use App\Features\Cymbio\Dto\CymbioBasicInfoDto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

/**
 * Class ProductStockRepo
 * @package App\Features\Cymbio
 */
class ProductStockRepo
{

    /** @var int */
    const DEFAULT_VALUE_ID_SHOP = 1;

    /** @var int */
    const DEFAULT_VALUE_ID_SHOP_GROUP = 0;

    /** @var int */
    const DEFAULT_VALUE_DEPENDS_ON_STOCK = 0;

    /** @var int */
    const DEFAULT_VALUE_OUT_OF_STOCK = 0;

    /** @var int */
    const DEFAULT_VALUE_ATTRIBUTE = 0;

    /** @var int */
    const IS_DELETED = false;

    /** @var int */
    const ZERO_ATTRIBUTE = 0;

    /** @var Product */
    private $product;

    /** @var ProductAttribute */
    private $productAttribute;

    /** @var StockAvailable */
    private $stockAvailable;

    /** @var ProductAttributeCombination */
    private $attributeCombination;

    /** @var ProductShop */
    private $productShop;

    /**
     * ProductStockRepo constructor.
     * @param Product $product
     * @param ProductAttribute $productAttribute
     * @param StockAvailable $stockAvailable
     * @param ProductAttributeCombination $attributeCombination
     * @param ProductShop $productShop
     */
    public function __construct(
        Product $product,
        ProductAttribute $productAttribute,
        StockAvailable $stockAvailable,
        ProductAttributeCombination $attributeCombination,
        ProductShop $productShop
    )
    {
        $this->product              = $product;
        $this->productAttribute     = $productAttribute;
        $this->stockAvailable       = $stockAvailable;
        $this->attributeCombination = $attributeCombination;
        $this->productShop          = $productShop;
    }

    /**
     * @param Product $product
     * @param CymbioBasicInfoDto $productDto
     * @return Collection
     */
    public function createProductStock(Product $product, CymbioBasicInfoDto $productDto): Collection
    {
        return collect($product->productAttribute()->get())
            ->map(function (ProductAttribute $productAttribute) use ($productDto) {
                $quantity       = ($productDto->isOneSize) ?
                    $productDto->totalInventory :
                    $this->getQuantity($productDto->sizeInventory, $productAttribute->id_product_attribute);
                $stockAvailable = $this->stockAvailable->newInstance([
                    'id_product'           => $productAttribute->id_product,
                    'id_product_attribute' => $productAttribute->id_product_attribute,
                    'id_shop'              => self::DEFAULT_VALUE_ID_SHOP,
                    'id_shop_group'        => self::DEFAULT_VALUE_ID_SHOP_GROUP,
                    'quantity'             => $quantity,
                    'depends_on_stock'     => self::DEFAULT_VALUE_DEPENDS_ON_STOCK,
                    'out_of_stock'         => self::DEFAULT_VALUE_OUT_OF_STOCK,
                ]);
                $stockAvailable->save();
            });
    }

    /**
     * @param Product $product
     * @param int $total
     * @return Collection
     */
    public function createProductStockAttribute(Product $product, int $total): Collection
    {
        return collect(collect($product->productAttribute()->get())
            ->first(function (ProductAttribute $productAttribute) use ($total) {
                $stockAvailable = $this->stockAvailable->newInstance([
                    'id_product'           => $productAttribute->id_product,
                    'id_product_attribute' => self::DEFAULT_VALUE_ATTRIBUTE,
                    'id_shop'              => self::DEFAULT_VALUE_ID_SHOP,
                    'id_shop_group'        => self::DEFAULT_VALUE_ID_SHOP_GROUP,
                    'quantity'             => $total,
                    'depends_on_stock'     => self::DEFAULT_VALUE_DEPENDS_ON_STOCK,
                    'out_of_stock'         => self::DEFAULT_VALUE_OUT_OF_STOCK,
                ]);
                return $stockAvailable->save();
            })
        );
    }

    /**
     * @param CymbioBasicInfoDto $productDto
     * @return Collection
     */
    public function updateProductStock(CymbioBasicInfoDto $productDto): Collection
    {
        $product = $this->getProductBySupplierSku($productDto);

        if ($productDto->isOneSize) {
            $productAttribute         = $this->getProductAttributeByProductId($product->id_product);
            $stockAvailable           = $this->getStockAvailable($product->id_product, $productAttribute);
            $stockAvailable->quantity = $productDto->totalInventory;
            return collect([$stockAvailable->save()]);
        }

        return $productDto->sizeInventory
            ->map(function (Collection $attribute, $key) use ($product) {
                if (empty($key)) {
                    throw new UnexpectedValueException('$key為空');
                }
                $attributeCombination = $this->attributeCombination
                    ->where([
                        ['id_attribute', '=', $key],
                    ])
                    ->get();

                $productAttribute = $this->getProdAttribute($attributeCombination, $product->id_product);
                if ($productAttribute->count() === 0) {
                    Log::channel('consign')->info('[updateProductStock 抓不到資料]', [
                        'idProduct' => $product->id_product,
                        'id_attribute' => $key,
                        'attributeCombination' => json_encode($attributeCombination),
                    ]);
                    return true;
                }
                $stockAvailable           = $this->getStockAvailable($product->id_product, $productAttribute);
                $stockAvailable->quantity = $attribute->first();
                return $stockAvailable->save();
            });
    }

    /**
     * @param CymbioBasicInfoDto $productDto
     * @return Collection
     */
    public function updateProductStockTotal(CymbioBasicInfoDto $productDto): Collection
    {
        $product            = $this->getProductBySupplierSku($productDto);
        $stockAvailableList = $this->stockAvailable
            ->where([
                ['id_product', '=', $product->id_product],
                ['id_shop', '=', self::DEFAULT_VALUE_ID_SHOP],
                ['id_shop_group', '=', self::DEFAULT_VALUE_ID_SHOP_GROUP],
                ['id_product_attribute', '=', self::ZERO_ATTRIBUTE],
            ])
            ->get();

        return collect($stockAvailableList)
            ->map(function (StockAvailable $stockAvailable) use ($productDto) {
                $stockAvailable->quantity = $productDto->totalInventory;
                return $stockAvailable->save();
            });
    }

    /**
     * 更新價格
     * @param CymbioBasicInfoDto $productDto
     * @return Collection
     * @throws UnexpectedValueException
     */
    public function updateProductPrice(CymbioBasicInfoDto $productDto): Collection
    {
        $product     = $this->getProductBySupplierSku($productDto);
        $productList = $this->product
            ->where([
                ['id_product', '=', $product->id_product],
            ])
            ->get();
        if ($productList->count() !== 1) {
            throw new UnexpectedValueException('無法對應到正確的 product 資料，無法更新數量');
        }

        return collect($productList)
            ->map(function (Product $product) use ($productDto) {
                $product->price                    = $productDto->retailPrice;
                $product->supplier_retail          = $productDto->retailPrice;
                $product->supplier_wholesale_price = $productDto->wholesalePrice;
                $product->save();
            });
    }


    /**
     * 更新價格
     * @param CymbioBasicInfoDto $productDto
     * @return Collection
     */
    public function updateProductAttributePrice(CymbioBasicInfoDto $productDto): Collection
    {
        $product     = $this->getProductBySupplierSku($productDto);
        $productList = $this->productAttribute
            ->where([
                ['id_product', '=', $product->id_product],
            ])
            ->get();

        return collect($productList)->map(function (ProductAttribute $productAttribute) use ($productDto) {
            $productAttribute->price = $productDto->retailPrice;
            $productAttribute->save();
        });
    }

    /**
     * 更新價格
     * @param CymbioBasicInfoDto $productDto
     * @return Collection
     * @throws UnexpectedValueException
     */
    public function updateProductShopPrice(CymbioBasicInfoDto $productDto): Collection
    {
        $product     = $this->getProductBySupplierSku($productDto);
        $productList = $this->productShop
            ->where([
                ['id_product', '=', $product->id_product],
            ])
            ->get();

        if ($productList->count() !== 1) {
            throw new UnexpectedValueException('無法對應到正確的 product 資料，無法更新數量');
        }

        return collect($productList)
            ->map(function (ProductShop $productShop) use ($productDto) {
                $productShop->price = $productDto->retailPrice;
                $productShop->update();
            });
    }

    /**
     * @param CymbioBasicInfoDto $productDto
     * @return Product
     * @throws UnexpectedValueException
     */
    private function getProductBySupplierSku(CymbioBasicInfoDto $productDto): Product
    {
        $products = $this->product
            ->where('supplier_reference', '=', $productDto->reference)
            ->where('is_deleted', '=', self::IS_DELETED)
            ->get();

        if ($products->count() !== 1) {
            throw new UnexpectedValueException('對應的商品資料不正確');
        }

        return $products->first();
    }

    /**
     * @param Collection $products
     * @return int 影響的筆數
     */
    public function resetStockByProducts(Collection $products): int
    {
        return DB::table('ps_stock_available')
            ->whereIn('id_product', $products->pluck('idProduct'))
            ->update([
                'quantity' => 0,
            ]);
    }

    /**
     * @param $attributeCombination
     * @param int $idProduct
     * @return Collection
     */
    private function getProdAttribute($attributeCombination, int $idProduct): Collection
    {
        return $attributeCombination
            ->map(function (ProductAttributeCombination $combination) use ($idProduct) {
                return $this->productAttribute
                    ->where([
                        ['id_product', '=', $idProduct],
                        ['id_product_attribute', '=', (int)$combination->id_product_attribute],
                    ])
                    ->get();
            })->reject(function (Collection $item) {
                return $item->count() === 0;
            })->first() ?? collect([]);
    }

    /**
     * @param int $idProduct
     * @param $productAttribute
     * @return StockAvailable
     */
    private function getStockAvailable(int $idProduct, Collection $productAttribute): StockAvailable
    {
        $stockAvailable = $this->stockAvailable
            ->where([
                ['id_product', '=', $idProduct],
                ['id_shop', '=', self::DEFAULT_VALUE_ID_SHOP],
                ['id_shop_group', '=', self::DEFAULT_VALUE_ID_SHOP_GROUP],
                ['id_product_attribute', '=', $productAttribute->first()->id_product_attribute],
            ])
            ->first();

        if (is_null($stockAvailable)) {
            Log::error('can not fund stockAvailable', [
                'id_product'           => $idProduct,
                'id_shop'              => self::DEFAULT_VALUE_ID_SHOP,
                'id_shop_group'        => self::DEFAULT_VALUE_ID_SHOP_GROUP,
                'id_product_attribute' => $productAttribute->first()->id_product_attribute,
            ]);
            throw new UnexpectedValueException('can not fund stockAvailable');
        }

        return $stockAvailable;
    }

    /**
     * @param Collection $quantityObj
     * @param int $idProductAttribute
     * @return int
     */
    private function getQuantity(Collection $quantityObj, int $idProductAttribute): int
    {
        $attributeCombination = $this->attributeCombination
            ->where([
                ['id_product_attribute', '=', $idProductAttribute],
            ])
            ->get()
            ->groupBy('id_attribute');
        $quantities = $quantityObj->intersectByKeys($attributeCombination);
        $quantity = $quantities->first();

        if (is_null($quantity)) {
            return 0;
        }

        return $quantity->first();
    }

    /**
     * @param int $productId
     * @return Collection
     */
    private function getProductAttributeByProductId(int $productId): Collection
    {
        return $this->productAttribute
            ->where([
                ['id_product', '=', $productId],
            ])->get();
    }
}
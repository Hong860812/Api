<?php

namespace App\Features\Cymbio\Repo;

use App\Entities\Product;
use App\Entities\ProductAttribute;
use App\Entities\ProductAttributeCombination;
use App\Entities\ProductLang;
use App\Features\Cymbio\Dto\CymbioBasicInfoDto;
use Illuminate\Support\Collection;
use UnexpectedValueException;

/**
 * Class ProductAttributeRepo
 * @package App\Features\Cymbio\Repo
 */
class ProductAttributeRepo
{
    /** @var int */
    const IS_DELETED = false;

    /** @var ProductLang */
    private $productLang;

    /** @var Product */
    private $product;

    /** @var ProductAttributeCombination */
    private $attributeCombination;

    /** @var ProductAttribute */
    private $productAttribute;

    /**
     * ProductAttributeRepo constructor.
     * @param Product $product
     * @param ProductLang $productLang
     * @param ProductAttributeCombination $attributeCombination
     * @param ProductAttribute $productAttribute
     */
    public function __construct(Product $product, ProductLang $productLang, ProductAttributeCombination $attributeCombination, ProductAttribute $productAttribute)
    {
        $this->product              = $product;
        $this->productLang          = $productLang;
        $this->attributeCombination = $attributeCombination;
        $this->productAttribute     = $productAttribute;
    }

    /**
     * @param CymbioBasicInfoDto $productDto
     * @return Collection
     * @throws UnexpectedValueException
     */
    public function updateProductAttributeSku(CymbioBasicInfoDto $productDto): Collection
    {

        $product = $this->getProductBySupplierSku($productDto);

        if ($productDto->isOneSize) {
            $productAttribute                     = $this->getProductAttributeByProductId($product->id_product);
            $productAttribute->supplier_reference = $productDto->attributesReference->first();;
            return collect([$productAttribute->save()]);
        }
        return $productDto->attributesReference
            ->map(function (Collection $attribute, $key) use ($product) {

                if (empty($key)) {
                    throw new UnexpectedValueException('$key為空');
                }

                $attributeCombination = $this->attributeCombination
                    ->where([
                        ['id_attribute', '=', $key],
                    ])
                    ->get();
                $productAttributeId = $this->getProdAttribute($attributeCombination, $product->id_product);
                $productAttribute = $this->getProductAttribute($product->id_product, $productAttributeId);
                $productAttribute->supplier_reference = $attribute->first();
                return $productAttribute->save();
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
     * @param $attributeCombination
     * @param int $idProduct
     * @return Collection
     */
    private function getProdAttribute(Collection $attributeCombination, int $idProduct): Collection
    {
        $prodAttribute = $attributeCombination
            ->map(function (ProductAttributeCombination $combination) use ($idProduct) {

                return $this->productAttribute
                    ->where([
                        ['id_product', '=', $idProduct],
                        ['id_product_attribute', '=', (int)$combination->id_product_attribute],
                    ])
                    ->get();
            })
            ->reject(function (Collection $item) {
                return $item->count() === 0;
            })->first();

        if (is_null($prodAttribute)) {
            throw new UnexpectedValueException('沒有這個id_product');
        }

        return $prodAttribute;
    }

    /**
     * @param int $idProduct
     * @param $productAttribute
     * @return ProductAttribute
     */
    private function getProductAttribute(int $idProduct, Collection $productAttribute): ProductAttribute
    {
        $productAttribute = $this->productAttribute
            ->where([
                ['id_product', '=', $idProduct],
                ['id_product_attribute', '=', $productAttribute->first()->id_product_attribute],
            ])->first();
        return $productAttribute;
    }

    /**
     * @param int $productId
     * @return ProductAttribute
     */
    private function getProductAttributeByProductId(int $productId): ProductAttribute
    {
        return $this->productAttribute
            ->where([
                ['id_product', '=', $productId],
            ])->first();
    }
}
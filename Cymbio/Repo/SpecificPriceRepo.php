<?php


namespace App\Features\Cymbio\Repo;

use App\Entities\Product;
use App\Entities\SpecificPrice;
use App\Features\Cymbio\Dto\CymbioBasicInfoDto;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use UnexpectedValueException;

/**
 * Class SpecificPriceRepo
 * @package App\Features\Cymbio\Repo
 */
class SpecificPriceRepo
{
    /** @var int */
    const PRECISION = 2;

    /** @var int */
    const DIVISOR = 100;

    /** @var int 折扣數的基準 必須為 0 或 5 */
    const BASE_LINE = 5;

    /** @var SpecificPrice */
    public $specificPrice;

    /**
     * SpecificPriceRepo constructor.
     * @param SpecificPrice $specificPrice
     */
    public function __construct(SpecificPrice $specificPrice)
    {
        $this->specificPrice = $specificPrice;
    }

    /**
     * 取得商品的特價資訊
     * @param int $productId
     * @return Collection
     */
    public function getProductActiveSpecificPrice(int $productId): Collection
    {
        return $this->specificPrice
            ->where('from', '<=', Carbon::now())
            ->where('to', '>=', Carbon::now())
            ->where('id_product', '=', $productId)
            ->where('is_deleted', '=', false)
            ->get();
    }

    /**
     * 建立特價紀錄
     * @param Product $product
     * @param CymbioBasicInfoDto $productDto
     */
    public function createSpecificPrice(Product $product, CymbioBasicInfoDto $productDto): void
    {
        if (is_null($productDto->specificInfo)) {
            return;
        }
        $specificReduction = $this->getSpecificReduction($productDto);

        $specificPrice = $this->specificPrice->newInstance([
            'id_specific_price_rule' => '0',
            'id_cart'                => '0',
            'id_product'             => $product->id_product,
            'id_shop'                => 0,
            'id_shop_group'          => 0,
            'id_currency'            => 0,
            'id_country'             => 0,
            'id_group'               => 0,
            'id_customer'            => 0,
            'id_product_attribute'   => 0,
            'price'                  => -1,
            'from_quantity'          => 1,
            'reduction'              => $specificReduction,
            'reduction_tax'          => 1,
            'reduction_type'         => 'percentage',
            'from'                   => $productDto->specificInfo->start,
            'to'                     => $productDto->specificInfo->end,
            'is_final_sale'          => 0,
            'is_deleted'             => 0,
            'date_add'               => date("Y-m-d H:i:s"),
            'date_upd'               => date("Y-m-d H:i:s"),
        ]);
        $specificPrice->save();
    }

    /**
     * 取得商品的特價折數,因為廠商給的是金額,所以需要換算
     * @param CymbioBasicInfoDto $productDto
     * @return float
     */
    private function getSpecificReduction(CymbioBasicInfoDto $productDto): float
    {
        $complement = 0;
        $reduction  = round(($productDto->specificInfo->salePrice / $productDto->retailPrice), self::PRECISION);

        $remainder = ($reduction * self::DIVISOR) % self::BASE_LINE;
        if ($remainder > 0) {
            $complement = (self::BASE_LINE - $remainder) / self::DIVISOR;
        }
        return (float)round(1 - (($reduction + $complement)), self::PRECISION);
    }

    /**
     * 檢查特價的資訊辨別是否需要更新
     * @param CymbioBasicInfoDto $productDto
     * @throws UnexpectedValueException
     */
    public function checkSpecificPriceInfo(CymbioBasicInfoDto $productDto): void
    {
        if (is_null($productDto->specificInfo)) {
            return;
        }

        $product             = $this->getProductBySupplierSku($productDto);
        $specificInfoCollect = $this->getProductActiveSpecificPrice($product->id_product);
        $specificReduction   = $this->getSpecificReduction($productDto);
        if ($specificInfoCollect->isEmpty()) {
            $this->createSpecificPrice($product, $productDto);
            return;
        }

        $specificInfo = $specificInfoCollect->first();
        if (
            $productDto->specificInfo->start->toDateTimeString() !== Carbon::parse($specificInfo['from'])->toDateTimeString() ||
            $productDto->specificInfo->end->toDateTimeString() !== Carbon::parse($specificInfo['to'])->toDateTimeString() ||
            $specificReduction !== (float)round($specificInfo['reduction'], self::PRECISION)
        ) {
            $this->deleteProductSpecificPrice($specificInfo['id_specific_price']);
            $this->createSpecificPrice($product, $productDto);
        }
    }

    /**
     * 刪除商品的特價紀錄
     * @param int $specificPriceId
     */
    public function deleteProductSpecificPrice(int $specificPriceId): void
    {
        $this->specificPrice->where('id_specific_price', $specificPriceId)
            ->update(['is_deleted' => true, 'id_employee_del' => 0]);
    }

    /**
     * @param CymbioBasicInfoDto $productDto
     * @return Product
     * @throws UnexpectedValueException
     */
    private function getProductBySupplierSku(CymbioBasicInfoDto $productDto): Product
    {
        $products = Product::where('supplier_reference', '=', $productDto->reference)->get();
        if ($products->count() !== 1) {
            throw new UnexpectedValueException('對應的商品資料不正確');
        }
        return $products->first();
    }
}

<?php

namespace App\Features\Cymbio;

use App\Features\Cymbio\Dto\CymbioBasicInfoDto;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;


/**
 * Cymbio廠商商品的更新
 * Class CymbioProductUpdateService
 * @package App\Features\Cymbio
 */
class CymbioProductUpdateService extends CymbioAbsProductService
{
    /**
     * @return CymbioAbsProductService
     */
    public function init(): CymbioAbsProductService
    {
        $this->productsNeedToUpdate = $this->productsFromApi->intersectByKeys($this->productsInDb);
        return $this;
    }

    /**
     * valid 是否有定義必要的成員變數
     * @throws InvalidArgumentException
     */
    protected function valid(): void
    {
        parent::valid();
        if (empty($this->productsNeedToUpdate)) {
            throw new InvalidArgumentException('沒有定義 productsNeedToUpdate');
        }
    }


    /**
     * @return Collection
     */
    public function updateQty(): Collection
    {
        $this->valid();
        return $this->productsNeedToUpdate
            ->map(function (CymbioBasicInfoDto $productDto) {
                Log::channel('daily_consign')->info('更新商品', [
                    'sku'     => $productDto->reference,
                    'product' => $productDto,
                ]);

                try {
                    DB::beginTransaction();
                    $this->productStockRepo->updateProductStock($productDto);
                    $this->productStockRepo->updateProductStockTotal($productDto);
                    $this->productStockRepo->updateProductPrice($productDto);
                    $this->productStockRepo->updateProductShopPrice($productDto);
                    $this->productStockRepo->updateProductAttributePrice($productDto);
                    $this->specificPriceRepo->checkSpecificPriceInfo($productDto);
                    DB::commit();
                } catch (Exception $ex) {
                    Log::channel('daily_consign')->debug('無法更新商品', [
                        'sku'     => $productDto->reference,
                        'message' => $ex->getMessage(),
                    ]);
                    DB::rollBack();
                    $result = self::RESULT_FAIL;
                }

                return [
                    'sku'    => $productDto->reference,
                    'result' => $result ?? self::RESULT_SUCCESS,
                ];
            });
    }
}
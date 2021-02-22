<?php


namespace App\Features\Cymbio;

use App\DTO\ProductStoreDTO;
use App\Features\Cymbio\Dto\CymbioApiImageDto;
use App\Features\Cymbio\Dto\CymbioBasicInfoDto;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use UnexpectedValueException;


/**
 * Class CymbioProductCreateService
 * Cymbio廠商商品的新增
 * @package App\Features\Cymbio
 */
class CymbioProductCreateService extends CymbioAbsProductService
{
    /** @var int */
    const SUPPLIER_WHOLE_SALE_PRICE_CURRENCY_ID = 1;

    /** @var string 商品的類別 */
    const PRODUCT_TYPE = 'consignment';

    /**
     * @return $this
     */
    public function init(): CymbioAbsProductService
    {
        $this->productsNeedToCreate = $this->productsFromApi->diffKeys($this->productsInDb);
        return $this;
    }

    /**
     * @return void
     * @throws InvalidArgumentException
     */
    protected function valid(): void
    {
        parent::valid();
        if (empty($this->productsNeedToCreate)) {
            throw new InvalidArgumentException('沒有定義 productsNeedToCreate');
        }
    }

    /**
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function updateQty(): Collection
    {
        $this->valid();

        return $this->productsNeedToCreate
            ->map(function (CymbioBasicInfoDto $productDto) {
                try {
                    DB::beginTransaction();
                    $productCreateDto = $this->generateProductDto($productDto);
                    $product          = $this->createService->create($productCreateDto);
                    $this->productStockRepo->createProductStock($product, $productDto);
                    $this->productLangRepo->createDescription($product, $productDto->description);
                    $this->productStockRepo->createProductStockAttribute($product, $productDto->totalInventory);
                    $this->productAttributeRepo->updateProductAttributeSku($productDto);
                    $this->specificPriceRepo->createSpecificPrice($product, $productDto);
                    DB::commit();
                } catch (Exception|UnexpectedValueException $ex) {
                    Log::channel('daily_consign')->debug('無法新增商品', [
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

    /**
     * 產生要存入資料庫的 DTO
     * @param CymbioBasicInfoDto $product
     * @return ProductStoreDTO
     * @throws UnexpectedValueException
     */
    private function generateProductDto(CymbioBasicInfoDto $product): ProductStoreDTO
    {
        if (empty($product->reference)) {
            throw new UnexpectedValueException('沒有 sku 無法建立商品');
        }

        return new ProductStoreDTO(
            $this->supplierId,
            $this->manufacturerId,
            $this->seasonId,
            $this->getProductNames($product->name),
            $product->reference,
            $product->color,
            '',
            $product->wholesalePrice,
            self::SUPPLIER_WHOLE_SALE_PRICE_CURRENCY_ID,
            $product->retailPrice,
            $product->retailPrice,
            $product->categoryId,
            [$product->categoryId],
            [],
            $product->sizeIds->all(),
            [],
            null,
            self::PRODUCT_TYPE,
            '',
            $this->getImages($product->imagesUrl)
        );
    }

    /**
     * 要存入資料庫的 product name 需依 lang 排列
     * @param string $productName
     * @return array
     */
    private function getProductNames(string $productName): array
    {
        return collect((array)config('setting.lang'))
            ->mapWithKeys(function (int $langId) use ($productName) {
                return [
                    $langId => $productName,
                ];
            })
            ->toArray();
    }

    /**
     * 取得所有的 image
     * @param Collection $urls
     * @return array
     */
    private function getImages(Collection $urls): array
    {
        return $urls->reduce(function (array $uploadedFiles, CymbioApiImageDto $imageDto) {
            $uploadedFiles[] = $this->getImage($imageDto);
            return $uploadedFiles;
        }, []);
    }

    /**
     * 取得指定的 image
     * @param CymbioApiImageDto $imageDto
     * @return UploadedFile
     */
    private function getImage(CymbioApiImageDto $imageDto): UploadedFile
    {
        $key = $imageDto->id;
        $url = App::environment('testing') ? env('APP_URL') . '/img/404.gif' : str_replace(' ', '%20', $imageDto->src);

        $directory = env('ROOT') . '/upload';
        $filename = 'tmp' . Str::slug($key, '') . '.jpg';
        $fullName = $directory . '/' . $filename;
        $this->imageDownloader->download($url, $directory, $filename);

        return new UploadedFile($fullName, $filename);
    }
}
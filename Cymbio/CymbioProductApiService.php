<?php

namespace App\Features\Cymbio;


use App\Features\Cymbio\Dto\CymbioApiImageDto;
use App\Features\Cymbio\Dto\CymbioBasicInfoDto;
use App\Features\Cymbio\Dto\CymbioSpecialPriceDto;
use App\Features\Cymbio\Repo\AttributeLangRepo;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

/**
 * Class CymbioProductApiService
 * 從 api 取得商品
 * @package App\Features\Cymbio
 */
abstract class CymbioProductApiService extends CymbioApiService
{
    /** @var int */
    const HTTP_SUCCESS = 200;

    /** @var int */
    const PRECISION = 2;

    /** @var int */
    const DIVISOR = 100;

    /** @var string */
    const BASE_URL = '%s/retailers/%s/variants?service_type=ecommerce&limit=1000&offset=%s';

    /** @var AttributeLangRepo */
    protected AttributeLangRepo $attributeLangRepo;

    /**
     * CymbioProductApiService constructor.
     * @param AttributeLangRepo $attributeLangRepo
     * @param Client $client
     */
    public function __construct(AttributeLangRepo $attributeLangRepo, Client $client)
    {
        parent::__construct($client);
        $this->attributeLangRepo = $attributeLangRepo;
    }

    /**
     * @return Collection
     */
    public function getProduct(): Collection
    {
        $this->setHeaderInfo();
        $this->getJsonFromApi(0);
        return $this->formatProducts();
    }

    /**
     * @param int $offset
     * @return Collection
     */
    public function getProductWithOffset(int $offset): Collection
    {
        $this->setHeaderInfo();
        $this->getJsonFromApi($offset);
        return $this->formatProducts();
    }

    /**
     * @return Collection
     */
    abstract public function formatProducts(): Collection;

    /**
     * 取得 api 路徑
     * @param int $offset
     * @return string
     */
    private function generateUrl(int $offset)
    {
        $baseURL = config('cymbio.' . env('APP_ENV') . '.BASE_URL');
        $retailerId = $this->retailId;
        return sprintf(self::BASE_URL, $baseURL, $retailerId, $offset);
    }

    /**
     * 從 api 取得 json
     * @param int $offset
     */
    private function getJsonFromApi(int $offset)
    {
        $url = $this->generateUrl($offset);

        try {
            $response = $this->client->request('GET', $url, ['headers' => $this->header]);
            if (empty($response) || $response->getStatusCode() !== self::HTTP_SUCCESS) {
                throw new UnexpectedValueException('[getJsonFromApi]抓取api失敗');
            }
            $this->json = $response->getBody();

        } catch (GuzzleException $ex) {
            Log::channel('consign')->error('[getJsonFromApi] 商品資料取得失敗', [
                'url' => $this->url,
                'headers' => $this->header,
                'message' => $ex->getMessage(),
            ]);
            throw new UnexpectedValueException('[getJsonFromApi] Service 錯誤');
        }
    }


    /**
     * 取得商品的基本資料
     * @param Collection $products
     * @return CymbioBasicInfoDto
     * @throws UnexpectedValueException
     */
    protected function formatProductInfo(Collection $products): CymbioBasicInfoDto
    {
        $productSpecificPriceInfo = ($this->getSpecificPriceInfo($products)->isEmpty()) ?
            null :
            $this->convertToSpecificInfoDto(collect($this->getSpecificPriceInfo($products)->shift()));

        $product = collect($products->first());

        $sizes = $this->getProductSize($products);

        $sizeIds = $this->getProductSizeIds($sizes);
        return new CymbioBasicInfoDto([
            'reference' => $product['modelWithColor'],
            'name' => $product['title'],
            'description' => $this->getProductDescription($product),
            'detail' => $product['description_extra'],
            'imagesUrl' => $this->convertImagesDto($product['images'], $product['model']),
            'retailPrice' => round($product['retail_price'] / self::DIVISOR, self::PRECISION),
            'color' => $product['options']['color'],
            'categoryId' => $this->getCategoryId(),
            'specificInfo' => $productSpecificPriceInfo,
            'wholesalePrice' => (is_null($productSpecificPriceInfo)) ?
                $this->getProductWholesalePrice($products) :
                $this->getWholesalePriceWithSpecificPriceInfo($productSpecificPriceInfo),
            'totalInventory' => $this->getTotalQty($products),
            'sizeInventory' => $this->getProductsSizeInfo($sizeIds, $products, 'available_inventory'),
            'sizeIds' => $sizeIds,
            'attributesReference' => $this->getProductsSizeInfo($sizeIds, $products, 'sku'),
            'isOneSize' => $sizes->isEmpty(),
        ]);
    }

    /**
     * 取得商品的 Description
     * @param Collection $product
     * @return string
     */
    private function getProductDescription(Collection $product)
    {
        $extraDescription = $product['description_bullets'];
        if (!empty($product['description_bullets']) && is_array($product['description_bullets'])) {
            $extraDescription = implode(' ', $product['description_bullets']);
        }
        return $product['description'] . ' ' . $product['description_extra'] . ' ' . $extraDescription;
    }

    /**
     * 取得 collection 中的特價資料
     * @param Collection $products
     * @return Collection
     */
    private function getSpecificPriceInfo(Collection $products): Collection
    {
        return $products->where('special_sale_price_start', '!=', null);
    }

    /**
     * 取得 SIZES
     * @param Collection $products
     * @return Collection
     */
    protected function getProductSize(Collection $products): Collection
    {
        $product = $products->first();
        if (empty($product['options']['size']) || !isset($product['options']['size'])) {
            return collect([]);
        }

        return $products->map(function (array $item) {
            return $item['options']['size'];
        });

    }

    /**
     * @param Collection $sizes
     * @return Collection
     */
    abstract public function getProductSizeIds(Collection $sizes): Collection;

    /**
     * 轉成 image dto
     * @param array $images
     * @param string $sku
     * @return Collection
     */
    private function convertImagesDto(array $images, string $sku): Collection
    {
        return collect($images)
            ->map(function (string $item, int $key) use ($sku) {
                return new CymbioApiImageDto([
                    'id' => $sku . '_' . $key,
                    'src' => $item,
                ]);
            });
    }

    /**
     * @return int
     */
    abstract public function getCategoryId(): int;

    /**
     * 取得商品的 wholesale price
     * @param Collection $products
     * @return float
     * @throws UnexpectedValueException
     */
    private function getProductWholesalePrice(Collection $products): float
    {

        $product = $products->first();
        if ((float)$product['retail_price'] <= 0) {
            throw new UnexpectedValueException('價格金額錯誤');
        }
        return round(($product['retail_price'] / self::DIVISOR) * $this->commissionRate, self::PRECISION);
    }

    /**
     * 取得商品的 wholesale price
     * @param CymbioSpecialPriceDto|null $specificPriceInfo
     * @return float
     */
    private function getWholesalePriceWithSpecificPriceInfo(?CymbioSpecialPriceDto $specificPriceInfo): float
    {
        return round(($specificPriceInfo->salePrice) * $this->commissionRate, self::PRECISION);
    }

    /**
     * 取得商品總數量
     * @param Collection $products
     * @return int
     */
    private function getTotalQty(Collection $products): int
    {
        return $products->map(function (array $item) {
            return $item['available_inventory'];
        })->sum();
    }


    /**
     * 取得尺寸的資料
     * @param Collection $sizeIds
     * @param Collection $products
     * @param string $colName API 提供的資料欄位
     * @return Collection
     */
    protected function getProductsSizeInfo(Collection $sizeIds, Collection $products, string $colName): Collection
    {
        $product = $products->first();

        if (empty($product['options']['size']) || !isset($product['options']['size'])) {
            return collect([$product[$colName]]);
        }

        return $products->mapToGroups(function (array $item) use ($colName, $sizeIds) {
            return [$sizeIds->get($item['options']['size']) => $item[$colName]];
        });
    }

    /**
     * 將特價資訊轉換成 dto
     * @param Collection $productHasSpecificPrice
     * @return CymbioSpecialPriceDto
     */
    private function convertToSpecificInfoDto(Collection $productHasSpecificPrice): CymbioSpecialPriceDto
    {
        return new CymbioSpecialPriceDto([
            'salePrice' => round(($productHasSpecificPrice['special_sale_price'] / self::DIVISOR), self::PRECISION),
            'start' => Carbon::parse($productHasSpecificPrice['special_sale_price_start']),
            'end' => Carbon::parse($productHasSpecificPrice['special_sale_price_end']),
        ]);
    }

}
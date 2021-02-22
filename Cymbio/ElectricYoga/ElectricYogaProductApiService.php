<?php

namespace App\Features\Cymbio\ElectricYoga;

use App\Entities\AttributeLang;
use App\Features\Cymbio\CymbioProductApiService;
use App\Features\Cymbio\Repo\AttributeLangRepo;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Class ElectricYogaProductApiService
 * @package App\Features\Cymbio\ElectricYoga
 */
class ElectricYogaProductApiService extends CymbioProductApiService
{

    /** @var string */
    public string $json;

    /** @var string */
    public $scope;

    /** @var int */
    public $retailId;

    /** @var float */
    public float $commissionRate = 0.75;

    /** @var int */
    const CATEGORY_ID = 207;

    /** @var int */
    const ID_SIZE_ATTRIBUTE_GROUP = 192;

    /**
     * ElectricYogaProductApiService constructor.
     * @param AttributeLangRepo $attributeLangRepo
     * @param Client $client
     */
    public function __construct(AttributeLangRepo $attributeLangRepo, Client $client)
    {
        parent::__construct($attributeLangRepo, $client);
        $this->scope    = 'read:variants';
        $this->retailId = config('cymbio.' . env('APP_ENV') . '.ElectricYoga.RETAILER_ID');
    }

    /**
     * 整理商品資訊
     * @return Collection
     * @throws InvalidArgumentException
     */
    public function formatProducts(): Collection
    {
        return collect(json_decode($this->json, true))
            ->reject(function ($item) {
                return empty($item);
            })
            ->map(function (array $item) {
                $item['modelWithColor'] = $item['model'] . '-' . preg_replace('/\s+/', '', $item['options']['color']);
                return $item;
            })
            ->groupBy('modelWithColor')
            ->map(function (Collection $item) {
                try {
                    return $this->formatProductInfo($item);
                } catch (InvalidArgumentException $ex) {
                    Log::channel('daily_consign')->debug($ex->getMessage(), [
                        'item' => $item,
                    ]);
                }
            })->reject(function ($item) {
                return is_null($item);
            });
    }

    /**
     * 取得該商品的目錄 id
     * @return int
     */
    public function getCategoryId(): int
    {
        return self::CATEGORY_ID;
    }

    /**
     * 取得該尺寸的所對應的尺寸組群 id
     * @param Collection $sizes
     * @return Collection
     * @UnexpectedValueException
     */
    public function getProductSizeIds(Collection $sizes): Collection
    {
        if ($sizes->isEmpty()) {
            return collect([]);
        }
        $sizeMap = $this->attributeLangRepo->getSizesMappingToAttribute(self::ID_SIZE_ATTRIBUTE_GROUP, $sizes);

        if (count($sizes) !== count($sizeMap)) {
            throw new UnexpectedValueException('沒有對應的尺寸，不予以處理，但需記錄起來');
        }

        return $sizeMap->mapWithKeys(function (AttributeLang $item) {
            return [$item['name'] => $item['id_attribute']];
        });
    }


    /**
     * 取得 SIZES
     * @param Collection $products
     * @return Collection
     */
    public function getProductSize(Collection $products): Collection
    {
        $product = $products->first();
        if (empty($product['options']['size']) || !isset($product['options']['size'])) {
            return collect([]);
        }

        return $products->map(function (array $item) {
            return $this->convertSizeName($item['options']['size']);
        });
    }

    /**
     * 取得尺寸的資料
     * @param Collection $sizeIds
     * @param Collection $products
     * @param string $colName API 提供的資料欄位
     * @return Collection
     */
    public function getProductsSizeInfo(Collection $sizeIds, Collection $products, string $colName): Collection
    {
        $product = $products->first();
        if (empty($product['options']['size']) || !isset($product['options']['size'])) {
            return collect([$product[$colName]]);
        }

        return $products->mapToGroups(function (array $item) use ($colName, $sizeIds) {
            return [$sizeIds->get($this->convertSizeName($item['options']['size'])) => $item[$colName]];
        });
    }

    /**
     * 尺寸對照表
     * @param string $size
     * @return string
     */
    private function convertSizeName(string $size): string
    {
        $sizesTransfer = collect(["X-Small" => "XS", "Medium" => "M", "Small" => "S", "Large" => "L",
            "1X" => "XL", "2X" => "2XL", "X-Large" => "XL"]);

        if (!$sizesTransfer->has($size)) {
            return $size;
        }

        return $sizesTransfer->get($size);
    }
}

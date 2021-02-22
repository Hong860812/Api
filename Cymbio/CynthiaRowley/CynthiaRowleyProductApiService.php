<?php

namespace App\Features\Cymbio\CynthiaRowley;

use App\Entities\AttributeLang;
use App\Features\Cymbio\CymbioProductApiService;
use App\Features\Cymbio\Repo\AttributeLangRepo;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Class CynthiaRowleyProductApiService
 * @package App\Features\Cymbio\CynthiaRowley
 */
class CynthiaRowleyProductApiService extends CymbioProductApiService
{

    /** @var string */
    public string $json;

    /** @var string */
    public string $type;

    /** @var string */
    public $scope;

    /** @var int */
    public $retailId;

    /** @var float */
    public float $commissionRate = 0.5;

    /** @var int */
    const CATEGORY_ID = 223;

    /** @var int */
    const ID_SIZE_ATTRIBUTE_GROUP_SHOES = 55;

    /** @var int */
    const ID_SIZE_ATTRIBUTE_GROUP_USCLOTH = 46;

    /** @var int */
    const ID_SIZE_ATTRIBUTE_GROUP_INTERNATIONAL = 192;

    /** @var int */
    const ID_SIZE_ATTRIBUTE_GROUP_KIDS = 409;

    /** @var int */
    const CATEGORY_ID_SHOES = 207;

    /** @var int */
    const CATEGORY_ID_BAG = 4;

    /** @var int */
    const CATEGORY_ID_ACCESSORIES = 823;

    /**
     * CynthiaRowleyProductApiService constructor.
     * @param AttributeLangRepo $attributeLangRepo
     * @param Client $client
     */
    public function __construct(AttributeLangRepo $attributeLangRepo, Client $client)
    {
        parent::__construct($attributeLangRepo, $client);
        $this->scope = 'read:variants';
        $this->retailId = config('cymbio.' . env('APP_ENV') . '.CynthiaRowley.RETAILER_ID');
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
                $item['modelWithColor'] = $item['model'];
                return $item;
            })
            ->groupBy('model')
            ->map(function (Collection $item) {
                try {
                    $this->getProductType($item);
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
        $productCategoryMapping = ['Bag' => self::CATEGORY_ID_BAG,
            'Tote Bag' => self::CATEGORY_ID_BAG,
            'Tote' => self::CATEGORY_ID_BAG,
            'Shoes' => self::CATEGORY_ID_SHOES,
            'Bracelet' => self::CATEGORY_ID_ACCESSORIES,
            'Hat' => self::CATEGORY_ID_ACCESSORIES,
            'accessories' => self::CATEGORY_ID_ACCESSORIES];

        $categoryId = self::CATEGORY_ID;

        if (isset($productCategoryMapping[$this->type])) {
            $categoryId = $productCategoryMapping[$this->type];
        }

        return (int)$categoryId;
    }

    /**
     * 取得該尺寸的所對應的尺寸組群 id
     * @param Collection $sizes
     * @return Collection
     * @UnexpectedValueException
     */
    public function getProductSizeIds(Collection $sizes): Collection
    {
        try {
            if ($sizes->isEmpty()) {
                throw new UnexpectedValueException('尺寸為空');
            }

            switch ($this->type) {
                case 'Shoes':
                    $sizeMap = $this->attributeLangRepo->getSizesMappingToAttribute(self::ID_SIZE_ATTRIBUTE_GROUP_SHOES, $sizes);
                    break;
                default:
                    $sizeMap = $this->attributeLangRepo->getSizesMappingToAttribute(self::ID_SIZE_ATTRIBUTE_GROUP_USCLOTH, $sizes);

                    if (count($sizes) !== count($sizeMap)) {
                        $sizeMap = $this->attributeLangRepo->getSizesMappingToAttribute(self::ID_SIZE_ATTRIBUTE_GROUP_INTERNATIONAL, $sizes);
                    }
                    if (count($sizes) !== count($sizeMap)) {
                        $sizeMap = $this->attributeLangRepo->getSizesMappingToAttribute(self::ID_SIZE_ATTRIBUTE_GROUP_KIDS, $sizes);
                    }
                    break;
            }
            if (count($sizes) !== count($sizeMap)) {
                throw new UnexpectedValueException('沒有對應的尺寸，不予以處理，但需記錄起來');
            }
            return $sizeMap->mapWithKeys(function (AttributeLang $item) {
                return [$item['name'] => $item['id_attribute']];
            });
        } catch (UnexpectedValueException $ex) {
            $ex->getMessage();
            return collect([]);
        }
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
     * @param string $colName API 提供的資料欄位(available_inventory)
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
        $sizeArrayMapping = ["1X" => "2XL", "2X" => "3XL", "3X" => "4XL", "0/S" => "OS"];
        if (isset($sizeArrayMapping[$size])) {
            $size = $sizeArrayMapping[$size];
        }
        return $size;
    }


    /**
     * 取得商品類型
     * @param Collection $products
     * @return string
     */
    private function getProductType(Collection $products): string
    {
        $product = $products->first();
        $this->type = array_shift($product["properties"]["type"]);

        return $this->type;
    }

}

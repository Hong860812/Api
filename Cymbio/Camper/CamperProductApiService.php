<?php

namespace App\Features\Cymbio\Camper;

use App\Entities\AttributeLang;
use App\Features\Cymbio\CymbioProductApiService;
use App\Features\Cymbio\Dto\CymbioBasicInfoDto;
use App\Features\Cymbio\Repo\AttributeLangRepo;
use GuzzleHttp\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Class CamperProductApiService
 * @package App\Features\Cymbio\Camper
 */
class CamperProductApiService extends CymbioProductApiService
{

    /** @var string */
    public string $json;

    /** @var string */
    public $scope;

    /** @var int */
    public $retailId;

    /** @var float */
    public float $commissionRate = 0.8;

    /** @var int */
    const CATEGORY_ID = 207;

    /** @var int */
    const ID_SIZE_ATTRIBUTE_GROUP = 55;


    /**
     * CamperProductApiService constructor.
     * @param AttributeLangRepo $attributeLangRepo
     * @param Client $client
     */
    public function __construct(AttributeLangRepo $attributeLangRepo, Client $client)
    {
        parent::__construct($attributeLangRepo, $client);
        $this->scope    = 'read:variants';
        $this->retailId = config('cymbio.' . env('APP_ENV') . '.Camper.RETAILER_ID');
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
            ->reject(function ($item) {
                if (!isset($item['model']) || empty($item['model'])) {
                    Log::channel('daily_consign')->debug('商品reference不存在或為空', [
                        'item' => $item,
                    ]);

                    return true;
                }

                return false;
            })
            ->map(function (array $item) {
                $item['modelWithColor'] = $item['model'];
                return $item;
            })
            ->groupBy('modelWithColor')
            ->map(function (Collection $item) {
                try {
                    return $this->formatProductInfo($item);
                } catch (UnexpectedValueException|InvalidArgumentException $ex) {
                    Log::channel('daily_consign')->debug($ex->getMessage(), [
                        'item' => $item,
                    ]);
                }
            })
            ->reject(function (?CymbioBasicInfoDto $item) {
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
}

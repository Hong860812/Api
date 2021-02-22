<?php

namespace App\Features\Cymbio\Repo;

use App\Entities\AttributeLang;
use Illuminate\Support\Collection;
use UnexpectedValueException;
/**
 * Class AttributeLangRepo
 * @package App\Features\Cymbio\Repo
 */
class AttributeLangRepo
{
    /** @var AttributeLang */
    private $attributeLang;

    /**
     * AttributeLangRepo constructor.
     * @param AttributeLang $attributeLang
     */
    public function __construct(AttributeLang $attributeLang)
    {
        $this->attributeLang = $attributeLang;
    }

    /**
     * 取得該商品的尺寸 id
     * @param int $attributeGroupId
     * @param Collection $sizes
     * @return Collection
     * @throws UnexpectedValueException
     */
    public function getSizesMappingToAttribute(int $attributeGroupId, Collection $sizes): Collection
    {
        return $this->attributeLang->select('ps_attribute_lang.id_attribute', 'name')
            ->leftJoin('ps_attribute', 'ps_attribute.id_attribute', '=', 'ps_attribute_lang.id_attribute')
            ->whereIn('name', $sizes->toArray())
            ->where('id_lang', 1)
            ->where('id_attribute_group', $attributeGroupId)
            ->groupBy('name')
            ->orderBy('id_attribute')
            ->get();

    }

}
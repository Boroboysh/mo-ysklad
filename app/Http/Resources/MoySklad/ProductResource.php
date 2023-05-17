<?php

namespace App\Http\Resources\MoySklad;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Http;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array|false|mixed[]|string
     */
    public function toArray(Request $request)
    {
        // Категория (группа) товара
        //example href: https://online.moysklad.ru/api/remap/1.2/entity/productfolder/be9f8eb7-ef67-11ed-0a80-08600000f992
        $parentIdInitialString = $this->productFolder->meta->href ?? '';
        $parent_id = '';

        // Если категория есть, то извлекается её id из ссылки в объекте meta
        if ($parentIdInitialString != '') {
            $parent_id = substr($parentIdInitialString, strlen("https://online.moysklad.ru/api/remap/1.2/entity/productfolder/"));
        }


        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->salePrices[0]->value,
            'count' => $this->stock ?? 0,
            'priceold' => 0,
            'photo' => $this->photo,
            'text' => $this->description ?? '',
            'position' => 'desc', // ?
            'parentid' => $parent_id,
            'id_crm' => $this->article ?? '',
            'view' => true,
            'withproduct' => '',
            'viewvariation' => $this->variantsCount > 0,
            'isvariant' => false,
            'productid' => 0,
            'barcode' => $this->barcodes[0]->ean13,
            'step' => '',
            'cart_limit' => '',
            'ban_on_discount' => $this->discountProhibited
        ];
    }
}

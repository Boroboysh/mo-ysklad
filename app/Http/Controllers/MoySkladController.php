<?php

namespace App\Http\Controllers;

use App\Http\Resources\MoySklad\CategoryResource;
use App\Http\Resources\MoySklad\CustomerOrderResource;
use App\Http\Resources\MoySklad\ProductResource;
use App\Http\Resources\MoySklad\RetailstoreResource;
use App\Http\Resources\MoySklad\StoreResource;
use App\Http\Resources\MoySklad\UomResource;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MoySkladController extends Controller
{
    private string $base_url = "https://online.moysklad.ru/api/remap/1.2/entity";

    protected function pushData($pool, $path, $token): PromiseInterface|\Illuminate\Http\Client\Response {
        return $pool->withToken($token)->get($path['path']);
    }

    protected function getImagesForProduct(Request $request, $data): array {
        $ids = [];
        foreach ($data as $p) {
            $ids[] = $p->id;
        }

        $ret = [];
        foreach ($ids as $id) {
            $ret[] = [
                'id' => $id,
                'path' => "{$this->base_url}/product/{$id}/images"
            ];
        }

        $new_data = [];
        $photo = [];

        foreach (array_chunk($ret, 7) as $paths) {
            $paths = collect($paths);
            $responses = Http::pool(fn(Pool $pool) => $paths->map(fn($path) => $this->pushData($pool, $path, $request->bearerToken())));

            foreach ($responses as $r)
                foreach ($r['rows'] as $image) {
                    foreach ($ids as $id) {
                        $initialString = $image['meta']['href'];
                        $image_id = substr($initialString, strlen("{$this->base_url}/product/{$id}/images"));
                        $product_id_href = str_replace($image_id, '', $initialString);

                        if ($product_id_href === "{$this->base_url}/product/{$id}/images") {
                            $data = collect($data);
                            $photo[] = [
                                "width" => 0,
                                "height" => 0,
                                "path" => $image['meta']['downloadHref']
                            ];

                            $new_data = $data->map(function ($d) use ($photo, $image, $id) {
                                if ($d->id === $id) {
                                    $photo = json_encode($photo);
                                    $d->photo = $photo;
                                }
                                return $d;
                            });
                        }
                    }
                }
        }

        return $new_data->toArray();
    }

    protected function getReportStockAll(Request $request, $data): array {
        $ids = [];
        foreach ($data as $p) {
            $ids[] = $p->id;
        }

        $response = json_decode(Http::withToken($request->bearerToken())->get('https://online.moysklad.ru/api/remap/1.2/report/stock/all/current/'));
        $new_data = [];

        foreach ($response as $r) {
            foreach ($ids as $id) {
                if ($id === $r->assortmentId) {
                    $data = collect($data);

                    $new_data = $data->map(function ($d) use ($r, $id) {
                        if ($d->id === $id) {
                            $d->stock = $r->stock;
                        }
                        return $d;
                    });
                }
            }
        }

        return $new_data->toArray();
    }

    public function getAllData(Request $request) {
        $limit = $request->query('limit') ?? 1000;
        $offset = $request->query('offset') ?? 0;
        session(['bearer_token' => $request->bearerToken()]);

        $products = $this->requestToJson($request->bearerToken(), '/product?limit=' . $limit . '&offset=' . $offset);

        $new_products_array = array_replace(
            $products,
            $this->getReportStockAll($request, $products),
            $this->getImagesForProduct($request, $products)
        );


        return [
            'product' => ProductResource::collection($new_products_array),
            'product_folder' => CategoryResource::collection(
                $this->requestToJson(
                    $request->bearerToken(),
                    "/productfolder?limit={$limit}&offset={$offset}")
            ),
            'store' => StoreResource::collection(
                $this->requestToJson(
                    $request->bearerToken(),
                    "/store?limit={$limit}&offset={$offset}")
            ), //retailstore
            'retailstore' => RetailstoreResource::collection(
                $this->requestToJson(
                    $request->bearerToken(),
                    "/retailstore?limit={$limit}&offset={$offset}")
            ),
            'customerorder' => CustomerOrderResource::collection(
                $this->requestToJson(
                    $request->bearerToken(),
                    "/customerorder?limit={$limit}&offset={$offset}")
            ),
            'uom' => UomResource::collection(
                $this->requestToJson(
                    $request->bearerToken(),
                    "/uom?limit={$limit}&offset={$offset}")
            )
        ];
    }

    private function requestToJson($token, $endpoint)
    {
        $list = Http::withToken($token)->get($this->base_url . '' . $endpoint);
        $listToJson = json_decode($list);

        return $listToJson->rows;
    }
}

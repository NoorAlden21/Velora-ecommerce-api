<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\ProductFormMetaResource;
use App\Services\ProductFormMetaService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductFormMetaController extends Controller
{
    public function __construct(private ProductFormMetaService $service)
    {
    }

    public function index(Request $request): ProductFormMetaResource
    {
        $data = $this->service->get();

        $etag = sha1(json_encode($data));
        if ($request->headers->get('If-None-Match') === $etag) {
            abort(Response::HTTP_NOT_MODIFIED);
        }

        return (new ProductFormMetaResource($data))
            ->additional(['_etag' => $etag]);
    }
}

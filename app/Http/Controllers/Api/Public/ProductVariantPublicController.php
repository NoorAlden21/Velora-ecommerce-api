<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ResolveVariantRequest;
use App\Http\Resources\VariantResource;
use App\Models\Option;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\VariantKeyBuilder;
use Illuminate\Http\JsonResponse;
use Throwable;

final class ProductVariantPublicController extends Controller
{
    public function resolve(Product $product, ResolveVariantRequest $request): JsonResponse
    {
        try {
            $selections = $request->normalizedSelections(); // ['color'=>'black','size'=>'42', ...]

            // options and its slug
            $product->load(['options', 'selectedOptionValues.option']);
            $optionSlugToId = $product->options->mapWithKeys(fn ($o) => [$o->slug => $o->id]);

            // For each option, build map of allowed value slugs -> ids (from selectedOptionValues)
            $allowed = [];
            $grouped = $product->selectedOptionValues->groupBy(fn ($v) => $v->option->slug);
            foreach ($grouped as $optSlug => $vals) {
                $allowed[$optSlug] = $vals->mapWithKeys(fn ($v) => [$v->slug => $v->id])->toArray();
            }

            // Normalize & validate selections; build [option_slug => value_slug]
            $pairs = [];
            foreach ($selections as $optSlug => $valSlug) {
                if (!isset($optionSlugToId[$optSlug])) {
                    return response()->json(['message' => "Unknown option: {$optSlug}"], 422);
                }
                if (!isset($allowed[$optSlug][$valSlug])) {
                    return response()->json(['message' => "Invalid value '{$valSlug}' for option '{$optSlug}'"], 422);
                }
                $pairs[$optSlug] = $valSlug;
            }

            // Build canonical variant_key
            $key = VariantKeyBuilder::makeFromSlugs($pairs);

            // Find variant
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('variant_key', $key)
                ->with(['values.option', 'product'])
                ->first();

            if (!$variant) {
                return response()->json(['message' => 'Variant not found'], 404);
            }

            return (new VariantResource($variant))->response();
        } catch (Throwable $e) {
            report($e);
            return response()->json(['message' => 'Failed to resolve variant'], 500);
        }
    }
}

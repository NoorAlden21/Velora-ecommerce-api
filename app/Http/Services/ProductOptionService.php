<?php

namespace App\Services;

use App\Models\OptionValue;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class ProductOptionService
{
    public function attachOptionsAndValues(Product $product, array $data): void
    {
        DB::transaction(function () use ($product, $data) {
            $rows = $data['options']; // ['option_id'=>X, 'value_ids'=>[...]]
            $optionIds = collect($rows)->pluck('option_id')->unique()->values()->all();

            // Sync product_options to provided options
            $product->options()->sync($optionIds);

            // Validate that each value_id belongs to its option_id (one query for all)
            $valueIds = collect($rows)->flatMap(fn ($r) => $r['value_ids'])->unique()->values();
            $values = OptionValue::whereIn('id', $valueIds)->get(['id', 'option_id']);
            $validMap = $values->groupBy('option_id')->map(fn ($g) => $g->pluck('id')->all());

            foreach ($rows as $r) {
                $oid = $r['option_id'];
                $allowed = $validMap[$oid] ?? [];
                foreach ($r['value_ids'] as $vid) {
                    if (!in_array($vid, $allowed, true)) {
                        throw new \RuntimeException("Value {$vid} does not belong to option {$oid}");
                    }
                }
            }

            // Sync product_option_values (per option_id)
            //    keep only provided value_ids for these option_ids; remove others.
            $keepTuples = [];
            foreach ($rows as $r) {
                foreach ($r['value_ids'] as $vid) {
                    $keepTuples[] = ['product_id' => $product->id, 'option_id' => $r['option_id'], 'option_value_id' => $vid];
                }
            }

            // delete old rows for these options that are not in keepTuples
            DB::table('product_option_values')
                ->where('product_id', $product->id)
                ->whereIn('option_id', $optionIds)
                ->whereNotIn(DB::raw('(option_id, option_value_id)'), collect($keepTuples)->map(fn ($t) => DB::raw("({$t['option_id']}, {$t['option_value_id']})"))->toArray() ?: [DB::raw('(NULL, NULL)')])
                ->delete();

            // upsert new/current
            DB::table('product_option_values')->upsert(
                $keepTuples,
                ['product_id', 'option_id', 'option_value_id'],
                [] // nothing to update
            );
        });
    }
}

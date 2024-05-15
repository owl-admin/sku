<?php

namespace Slowlyo\OwlSku\Services;

use Slowlyo\OwlSku\Sku;
use Illuminate\Support\Arr;
use Slowlyo\OwlSku\Models\Goods;
use Illuminate\Support\Facades\DB;
use Slowlyo\OwlAdmin\Services\AdminService;

class GoodsService extends AdminService
{
    protected string $modelName = Goods::class;

    public function store($data)
    {
        DB::beginTransaction();
        try {
            $this->saving($data);

            $columns = $this->getTableColumns();

            /** @var Goods $model */
            $model = $this->getModel();

            foreach ($data as $k => $v) {
                if (!in_array($k, $columns)) {
                    continue;
                }

                $model->setAttribute($k, $v);
            }

            $result = $model->save();

            if ($result) {
                $this->saved($model);
            }

            $this->saveSku($model, $data['sku']);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            admin_abort('保存失败');
        }

        return true;
    }

    public function update($primaryKey, $data)
    {
        DB::beginTransaction();
        try {
            $result = parent::update($primaryKey, $data);

            if ($result) {
                $this->saveSku(Goods::find($primaryKey), $data['sku']);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            admin_abort('保存失败');
        }

        return true;
    }

    public function saveSku(Goods $goods, $sku)
    {
        $sku = Sku::make()->parse($sku);

        // 清除原有信息
        $goods->specGroups()->delete();
        $goods->specs()->delete();
        $goods->skus()->delete();

        // 创建规格组
        foreach ($sku['groups'] as $index => $item) {
            $group = $goods->specGroups()->create(['name' => $item['group_name']]);

            $sku['groups'][$index]['id'] = $group->getKey();

            // 创建规格
            foreach ($sku['groups'][$index]['specs'] as $k => $v) {
                $spec = $group->specs()->create([
                    'goods_id' => $goods->getKey(),
                    'name'     => $v['spec'],
                ]);

                $sku['groups'][$index]['specs'][$k]['id'] = $spec->getKey();
            }
        }

        // 创建sku
        $skuList = [];
        foreach ($sku['skus'] as $item) {
            $_item = Arr::except($item, ['_specs']);

            $_item['sku_json'] = collect($item['_specs'])->map(function ($item) use ($sku) {
                $_group = collect($sku['groups'])->where('group_key', $item['group_key'])->first();

                return [
                    'group' => ['name' => $item['group_name'], 'id' => $_group['id']],
                    'spec'  => [
                        'name' => $item['spec'],
                        'id'   => collect($_group['specs'])->where('spec_key', $item['spec_key'])->value('id'),
                    ],
                ];
            })->toArray();

            $_item['spec_ids'] = collect($_item['sku_json'])->pluck('spec.id')->sort()->implode(',');

            $skuList[] = $_item;
        }

        $goods->skus()->createMany($skuList);
    }
}

<?php

namespace Slowlyo\OwlSku;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Slowlyo\OwlSku\Models\GoodsSku;
use Slowlyo\OwlSku\Models\GoodsSpec;
use Slowlyo\OwlSku\Models\GoodsSpecGroup;
use Slowlyo\OwlAdmin\Renderers\ComboControl;

class Sku
{
    public static function make()
    {
        return new self();
    }

    /**
     * SKU 表单
     *
     * @param string $name       字段名
     * @param string $label      字段标签
     * @param array  $skuColumns sku 的字段信息, 数组格式的 amis 表单组件
     *
     * @return ComboControl
     */
    public function form(string $name = 'sku', string $label = 'SKU', array $skuColumns = [], $static = false)
    {
        $key       = 'sku_' . $name;
        $serviceId = $key . '_service';
        $comboId   = $name . '_combo';

        return amis()->ComboControl($name, $label)
            ->id($comboId)
            ->multiLine()
            ->strictMode(false)
            ->noBorder()
            ->required()
            ->items([
                amis()->ComboControl('groups')
                    ->multiple()
                    ->multiLine()
                    ->addButtonText('添加规格组')
                    ->validateOnChange()
                    ->hidden($static)
                    ->items([
                        amis()->TextControl('group_name', '规格名称')->required()->set('unique', true),
                        amis()->ComboControl('specs', '规格值')
                            ->validateOnChange()
                            ->multiple()
                            ->draggable()
                            ->addButtonText('添加规格')
                            ->items([
                                amis()->TextControl('spec')->set('unique', true)->required(),
                            ]),
                    ]),
                amis()->Divider()->visibleOn('${groups}')->hidden($static),
                amis()->VanillaAction()
                    ->level('success')
                    ->label('生成 SKU')
                    ->className('mb-3')
                    ->onEvent(['click' => ['actions' => [['actionType' => 'rebuild', 'componentId' => $serviceId]]]])
                    ->hidden($static)
                    ->visibleOn('${groups}'),
                // 通过 service 在后端生成sku结构
                amis()->Service()->id($serviceId)->showErrorMsg(false)->schemaApi([
                    'url'    => '/owl-sku/generate',
                    'method' => 'post',
                    'data'   => [
                        'goods_id'    => '${id}',
                        'groups'      => '${groups}',
                        'sku_name'    => $name,
                        'sku_columns' => $skuColumns,
                        'static'      => $static,
                    ],
                ]),
            ]);
    }

    /**
     * 提取 skus 信息
     *
     * @param $data
     *
     * @return array|mixed
     */
    public function getSkus($data)
    {
        $key = collect($data)->map(function ($item, $index) {
            if (!Str::startsWith($index, 'skus_')) {
                return null;
            }

            return $item;
        })->filter()->keys()->map(fn($i) => Str::replace('skus_', '', $i))->sortDesc()->first();

        if (blank($key)) {
            return [];
        }

        return data_get($data, 'skus_' . $key, []);
    }

    /**
     * 验证 sku 数量与规格组数量是否匹配
     *
     * @param      $data
     * @param bool $abort
     *
     * @return string|null
     */
    public function validate($data, bool $abort = true)
    {
        $specGroup = $data['groups'];

        $spec = collect($specGroup)->pluck('specs')->map(fn($item) => array_column($item, 'spec'))->toArray();

        $specCrossJoin = Arr::crossJoin(...$spec);

        $skus = $this->getSkus($data);

        if (count($skus) !== count($specCrossJoin)) {
            admin_abort_if($abort, 'sku 数量与规格组数量不匹配');

            return 'sku 数量与规格组数量不匹配';
        }

        return null;
    }

    /**
     * 解析表单提交的 sku 数据
     *
     * @param $data
     *
     * @return array
     */
    public function parse($data)
    {
        $groups = collect($data['groups'])->map(function ($item) {
            $item['group_key'] = md5($item['group_name']);

            $item['specs'] = collect($item['specs'])->map(function ($val) use ($item) {
                $val['spec_key'] = md5($item['group_name'] . $val['spec']);
                return $val;
            })->toArray();
            return $item;
        })->toArray();

        $groupKeys = Arr::pluck($groups, 'group_key');

        $skus = collect($this->getSkus($data))->map(function ($item) use ($groups, $groupKeys) {
            $_specs = [];
            foreach ($item as $k => $v) {
                if (in_array($k, $groupKeys)) {
                    unset($item[$k]);

                    $_group = collect($groups)->where('group_key', $k)->first();
                    $_spec  = collect($_group['specs'])
                        ->where('spec_key', md5($_group['group_name'] . $v))
                        ->first();

                    $_specs[] = [
                        'group_name' => $_group['group_name'],
                        'group_key'  => $_group['group_key'],
                        'spec'       => $_spec['spec'],
                        'spec_key'   => $_spec['spec_key'],
                    ];
                }
            }

            $item['_specs'] = $_specs;

            return $item;
        })->toArray();

        return compact('groups', 'skus');
    }

    /**
     * 回显规格数据
     *
     * @param $goodsId
     *
     * @return array
     */
    public function echoData($goodsId)
    {
        $groups = GoodsSpecGroup::with('specs')->where('goods_id', $goodsId)->get()->map(fn($item) => [
            'group_name' => $item->name,
            'specs'      => $item->specs->map(fn($val) => ['spec' => $val->name])->toArray(),
        ])->toArray();

        return compact('groups');
    }

    /**
     * 合并已有 sku 数据
     *
     * @param int   $goodsId 商品 id
     * @param array $value   sku 数据
     *
     * @return void
     */
    public function mergeExistsData(int $goodsId, array &$value)
    {
        $skus = GoodsSku::where('goods_id', $goodsId)->get()->map(function ($item) {
            // 计算 规格组 + 规格值 的 md5
            $skuSpecs = collect($item->sku_json)
                ->map(fn($item) => md5(md5($item['group']['name']) . $item['spec']['name']))
                ->toArray();

            $item->setAttribute('skuSpecs', $skuSpecs);

            return $item;
        })->toArray();

        foreach ($value as &$item) {
            $_skuSpecs = collect($item)->map(fn($v, $k) => md5($k . $v))->values()->toArray();
            // 根据 md5 匹配 sku
            $skuItem = collect($skus)->filter(fn($i) => !array_diff($_skuSpecs, $i['skuSpecs']))->first();

            if ($skuItem) {
                // 合并匹配到的数据
                $item = collect($skuItem)->except([
                    'id',
                    'goods_id',
                    'sku_ids',
                    'sku_json',
                    'created_at',
                    'updated_at',
                    'deleted_at',
                    'skuSpecs',
                ])->merge($item)->toArray();
            }
        }
    }
}

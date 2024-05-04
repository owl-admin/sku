# Owl Admin 扩展: SKU

## !! 前提

1. 需要熟悉常规商城 `sku` 的处理逻辑
2. 熟练使用 `laravel` 以及 `Owl Admin`
3. 拥有自行查阅源码的能力 (重点)

## 使用说明

### 数据表

#### goods (商品表)

默认只有一个 `name` 字段, 实际使用请根据需求自行添加

| 字段   | 说明   |
|------|------|
| id   |      |
| name | 商品名称 |

#### goods_spec_groups (规格组)

| 字段       | 说明    |
|----------|-------|
| id       |       |
| goods_id | 所属商品  |
| name     | 规格组名称 |

#### goods_specs (规格值)

| 字段       | 说明    |
|----------|-------|
| id       |       |
| goods_id | 所属商品  |
| group_id | 所属规格组 |
| name     | 规格名称  |

#### goods_skus (sku)

默认只记录 `price` 和 `stock` 两个字段, 实际使用请根据需求自行调整

| 字段       | 说明              |
|----------|-----------------|
| id       |                 |
| goods_id | 所属商品            |
| sku_ids  | 规格id(逗号分隔,升序)   |
| price    | 价格              |
| stock    | 库存              |
| sku_json | sku 数据, json 格式 |

### 如何使用

#### 组件的使用

原理: 通过 `combo` 组件, 结合后端代码, 实现了一个灵活的 sku 编辑器

```php
use Slowlyo\OwlSku\Sku;

// ...

// 使用组件
amis()->Form()->body([
    Sku::make()->form(),

    // 自定义 name 和 label
    Sku::make()->form('my_sku', 'My SKU'),

    // 自定义 sku 字段
    // 默认为 price 和 stock 两个字段
    Sku::make()->form('my_sku', 'My SKU', [
        amis()->TextControl('custom_1', '自定义字段1'),
        amis()->TextControl('custom_2', '自定义字段2'),
        amis()->TextControl('custom_3', '自定义字段3'),
        // ...
    ]),
])
```

#### sku 数据保存

在 `Slowlyo\OwlSku\Services\GoodsService` 中, 已经处理了新增商品和编辑商品的逻辑

可直接使用或重写

#### sku 数据回显

```php
use Slowlyo\OwlSku\Sku;

// 需要确保 sku 组件同级的数据域中有 id 字段, 值为 goods 表的 id (用于回显 sku 列表)

// sku 字段的数据需要作特殊处理

public function show () {
    return $this->response()->success([
        'id' => $goodsId,
        'sku' => Sku::make->echoData($goodsId)
    ]);
}
```

### 开发注意事项

- sku 属于比较复杂(麻烦)的功能, 使用该扩展前请确保你拥有 __[前提]__ 中提到的能力 (小白勿扰~)
- 扩展中处理数据结构的逻辑, 作者也不一定记得清楚, 若有需要请自行断点查看
- __卸载扩展会移除所有表!!!__

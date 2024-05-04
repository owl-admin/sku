<?php

namespace Slowlyo\OwlSku\Models;

use Slowlyo\OwlAdmin\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsSku extends BaseModel
{
    use SoftDeletes;

    protected $casts = [
        'sku_json' => 'json',
    ];

    protected static $unguarded = true;

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    public function goods()
    {
        return $this->belongsTo(Goods::class);
    }
}

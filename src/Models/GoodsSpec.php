<?php

namespace Slowlyo\OwlSku\Models;

use Slowlyo\OwlAdmin\Models\BaseModel;
use Illuminate\Database\Eloquent\SoftDeletes;

class GoodsSpec extends BaseModel
{
    use SoftDeletes;

    protected static $unguarded = true;

    public function goods()
    {
        return $this->belongsTo(Goods::class);
    }

    public function group()
    {
        return $this->belongsTo(GoodsSpecGroup::class, 'group_id');
    }
}

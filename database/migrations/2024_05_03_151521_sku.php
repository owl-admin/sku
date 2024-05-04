<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('goods_spec_groups', function (Blueprint $table) {
            $table->comment('规格组');
            $table->id();
            $table->integer('goods_id')->default(0)->comment('商品id');
            $table->string('name')->comment('规格组名称');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('goods_specs', function (Blueprint $table) {
            $table->comment('规格值');
            $table->id();
            $table->integer('goods_id')->default(0)->comment('商品id');
            $table->integer('group_id')->default(0)->comment('规格组id');
            $table->string('name')->comment('规格值');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('goods', function (Blueprint $table) {
            $table->comment('商品');
            $table->id();
            $table->string('name')->comment('商品名称');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('goods_skus', function (Blueprint $table) {
            $table->comment('商品sku');
            $table->id();
            $table->integer('goods_id')->default(0)->comment('商品id');
            $table->string('sku_ids')->comment('规格id(逗号分隔,升序)');
            $table->decimal('price')->default(0)->comment('价格');
            $table->decimal('stock')->default(0)->comment('库存');
            $table->text('sku_json')->comment('sku数据');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_spec_groups');
        Schema::dropIfExists('goods_specs');
        Schema::dropIfExists('goods');
        Schema::dropIfExists('goods_skus');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('document_items', function (Blueprint $table) {
            $table->string('name')->nullable()->after('product_id');
        });

        DB::table('document_items')->orderBy('id')->each(function ($item) {
            $productName = $item->product_id
                ? DB::table('products')->where('id', $item->product_id)->value('name')
                : null;
            $description = $item->description;

            if ($productName && Str::startsWith($description, $productName.' — ')) {
                $description = Str::after($description, $productName.' — ');
            }

            DB::table('document_items')->where('id', $item->id)->update([
                'name' => $productName ?: Str::before($item->description, ' — '),
                'description' => $description,
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('document_items', fn (Blueprint $table) => $table->dropColumn('name'));
    }
};
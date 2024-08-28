<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Schema\Grammars\Grammar;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Grammar::macro('typeLtree', fn() => 'ltree');

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255)->nullable(false);
            $table->binary('value')->nullable(true);
            $table->addColumn('ltree', 'path')->nullable(false);

            $table->unique('path', null, 'btree');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};

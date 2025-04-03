<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('foto_pessoas', function (Blueprint $table) {
            $table->id('fpt_id');
            $table->foreignId('pes_id')->constrained('pessoas', 'pes_id')->onDelete('cascade');
            $table->date('fp_data')->nullable();
            $table->string('fp_bucket', 50)->nullable();
            $table->string('fp_hash', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('foto_pessoas');
    }
};

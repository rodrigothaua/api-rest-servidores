<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('lotacoes', function (Blueprint $table) {
            $table->id('lot_id');
            $table->foreignId('pes_id')->constrained('pessoas', 'pes_id')->onDelete('cascade');
            $table->foreignId('unid_id')->constrained('unidades', 'unid_id')->onDelete('cascade');
            $table->date('lot_data_lotacao')->nullable();
            $table->date('lot_data_remocao')->nullable();
            $table->string('lot_portaria', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lotacoes');
    }
};

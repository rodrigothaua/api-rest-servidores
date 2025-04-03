<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('enderecos', function (Blueprint $table) {
            $table->id('end_id');
            $table->string('end_tipo_logradouro', 20)->nullable();
            $table->string('end_logradouro', 200);
            $table->integer('end_numero')->nullable();
            $table->string('end_bairro', 100)->nullable();
            $table->foreignId('cid_id')->constrained('cidades', 'cid_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('enderecos');
    }
};

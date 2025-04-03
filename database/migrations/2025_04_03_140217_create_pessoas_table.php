<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pessoas', function (Blueprint $table) {
            $table->id('pes_id');
            $table->string('pes_nome', 200);
            $table->date('pes_data_nascimento')->nullable();
            $table->string('pes_sexo', 1)->nullable();
            $table->string('pes_mae', 200)->nullable();
            $table->string('pes_pai', 200)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pessoas');
    }
};

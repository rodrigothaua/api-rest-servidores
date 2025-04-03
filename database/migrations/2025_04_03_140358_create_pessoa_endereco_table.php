<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('pessoa_endereco', function (Blueprint $table) {
            $table->foreignId('pes_id')->constrained('pessoas', 'pes_id')->onDelete('cascade');
            $table->foreignId('end_id')->constrained('enderecos', 'end_id')->onDelete('cascade');
            $table->primary(['pes_id', 'end_id']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('pessoa_endereco');
    }
};

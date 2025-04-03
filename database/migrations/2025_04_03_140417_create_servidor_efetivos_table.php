<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('servidor_efetivos', function (Blueprint $table) {
            $table->foreignId('pes_id')->primary()->constrained('pessoas', 'pes_id')->onDelete('cascade');
            $table->string('se_matricula', 20);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('servidor_efetivos');
    }
};

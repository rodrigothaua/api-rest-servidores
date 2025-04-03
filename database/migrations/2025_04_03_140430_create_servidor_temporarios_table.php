<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('servidor_temporarios', function (Blueprint $table) {
            $table->foreignId('pes_id')->primary()->constrained('pessoas', 'pes_id')->onDelete('cascade');
            $table->date('st_data_admissao')->nullable();
            $table->date('st_data_demissao')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('servidor_temporarios');
    }
};

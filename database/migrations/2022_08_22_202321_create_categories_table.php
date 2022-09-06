<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('id_categoria_syscom');
            $table->string('nombre');
            $table->string('nivel');
            $table->timestamps();

            $table->unsignedBigInteger('id_categoria_padre')->nullable();
            $table->foreign('id_categoria_padre')->references('id')->on('categories')->nullable()->constrained();
            

        });

        
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('categories');
    }
};

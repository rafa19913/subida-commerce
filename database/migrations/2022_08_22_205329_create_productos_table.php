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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('id_producto_syscom');
            $table->string('titulo')->nullable();
            $table->string('modelo')->nullable();
            $table->string('stock')->nullable();
            $table->string('precio')->nullable();
            $table->longText('descripcion')->nullable();
            $table->string('img_portada')->nullable();

            $table->unsignedBigInteger('id_marca')->nullable();
            $table->foreign('id_marca')->references('id')->on('marcas');

            $table->unsignedBigInteger('id_categoria')->nullable();
            $table->foreign('id_categoria')->references('id')->on('categories');
            
         
            


            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('productos');
    }
};

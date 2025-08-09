<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProducersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('producers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Producer's name
            $table->string('discogs_id')->nullable(); // Discogs ID or API reference
            $table->timestamps();
        });

        Schema::create('producer_track', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('producer_id');
            $table->unsignedBigInteger('listening_history_id');
            $table->timestamps();
    
            $table->foreign('producer_id')->references('id')->on('producers')->onDelete('cascade');
            $table->foreign('listening_history_id')->references('id')->on('listening_history')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('producers');
    }
}
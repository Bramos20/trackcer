<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPopularityDataToListeningHistory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('listening_history', function (Blueprint $table) {
            $table->json('popularity_data')->nullable(); // To store raw Spotify track data for apple music songs for popularity data
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('listening_history', function (Blueprint $table) {
            //
        });
    }
}

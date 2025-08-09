<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateListeningHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('listening_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // Link to the user
            $table->string('track_id');           // Spotify track ID
            $table->string('track_name');
            $table->string('artist_name');
            $table->string('album_name')->nullable();
            $table->string('source', 25)->nullable();
            $table->string('played_at');          // Timestamp of when the track was played
            $table->timestamps();

        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('listening_history');
    }
}

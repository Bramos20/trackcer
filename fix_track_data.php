<?php

use Illuminate\Support\Facades\DB;

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tracks = DB::table('listening_history')->get();

foreach ($tracks as $track) {
    $decodedData = json_decode($track->track_data, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        DB::table('listening_history')
            ->where('id', $track->id)
            ->update(['track_data' => json_encode($decodedData)]);
    } else {
        echo "Invalid JSON for track ID: {$track->id}\n";
    }
}

echo "Track data fix complete!\n";

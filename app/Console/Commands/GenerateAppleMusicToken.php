<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AppleMusicService;

class GenerateAppleMusicToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apple-music:token {--save=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate an Apple Music developer token';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            $this->info('Generating Apple Music Developer Token...');
            $token = AppleMusicService::generateToken();
            $expiryTime = time() + (180 * 24 * 60 * 60);
            $formattedExpiry = date('Y-m-d H:i:s', $expiryTime);

            $this->info("\n=== Apple Music Developer Token ===");
            $this->line($token);
            $this->info("\nToken successfully generated!");
            $this->info("Token will expire on: {$formattedExpiry}");

            // Save to file option
            $filePath = $this->option('save');
            if ($filePath) {
                file_put_contents($filePath, $token);
                $this->info("Token saved to {$filePath}");
            } else {
                // Ask if user wants to save the token
                if ($this->confirm('Would you like to save this token to a file?', false)) {
                    $filePath = $this->ask('Enter file path to save the token', 'apple_music_token.txt');
                    file_put_contents($filePath, $token);
                    $this->info("Token saved to {$filePath}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("\nError generating token: {$e->getMessage()}");
            $this->line("\nPlease make sure you have the correct libraries installed and environment variables set.");
            return 1;
        }
    }
}

<?php

namespace App\Services;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use DateTimeImmutable;
use Illuminate\Support\Facades\Log;
use Firebase\JWT\JWT;


 class AppleMusicService
 {
     /**
      * Generate an Apple Music developer token using configured credentials.
      *
      * @return string The generated JWT token
      * @throws \Exception If token generation fails
      */
     public static function generateToken(): string
     {
         // Get credentials from environment variables
         $teamId = env('APPLE_TEAM_ID');
         $keyId = env('APPLE_MUSIC_KEY_ID');
         $privateKeyPath = env('APPLE_MUSIC_KEY_PATH', base_path('AuthKey_C9GP4AYG72.p8'));

         Log::info("Generating Apple Music token with:", [
             'team_id' => $teamId,
             'key_id' => $keyId,
             'key_path' => $privateKeyPath
         ]);

         try {
             // Read the private key
             $privateKey = file_get_contents($privateKeyPath);
             if (!$privateKey) {
                 throw new \Exception("Failed to read private key file at: {$privateKeyPath}");
             }

             // Current time and expiration time (180 days)
             $currentTime = time();
             $expiryTime = $currentTime + (180 * 24 * 60 * 60);

             // Prepare the JWT payload
             $payload = [
                 'iss' => $teamId,
                 'iat' => $currentTime,
                 'exp' => $expiryTime
             ];

             // Generate the JWT token
             $token = \Firebase\JWT\JWT::encode(
                 $payload,
                 $privateKey,
                 'ES256',
                 $keyId
             );

             // Calculate and format expiry date for logging
             $expiryDate = new \DateTime();
             $expiryDate->setTimestamp($expiryTime);
             $formattedExpiry = $expiryDate->format('Y-m-d H:i:s');

             Log::info("Apple Music token generated successfully", [
                 'expires_at' => $formattedExpiry
             ]);

             return $token;
         } catch (\Exception $e) {
             Log::error("Error generating Apple Music token: " . $e->getMessage(), [
                 'trace' => $e->getTraceAsString()
             ]);
             throw $e;
         }
     }


 }



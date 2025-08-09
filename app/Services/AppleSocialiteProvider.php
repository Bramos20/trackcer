<?php

namespace App\Services;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Illuminate\Support\Facades\File;

class AppleSocialiteProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = ['name', 'email'];

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://appleid.apple.com/auth/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://appleid.apple.com/auth/token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCodeFields($state = null)
    {
        $fields = parent::getCodeFields($state);

        $fields['response_mode'] = 'form_post';
        $fields['response_type'] = 'code id_token';

        return $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenResponse($code)
    {
        $clientSecret = $this->generateClientSecret();

        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => [
                'client_id' => $this->clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
                'redirect_uri' => $this->redirectUrl,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Generate the client secret using the private key.
     *
     * @return string
     */
    protected function generateClientSecret()
    {
        $keyPath = $this->getConfig('private_key_path');
        $keyId = $this->getConfig('key_id');
        $teamId = $this->getConfig('team_id');

        $privateKey = File::get(base_path($keyPath));

        $config = Configuration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($privateKey)
        );

        $now = new \DateTimeImmutable();

        $token = $config->builder()
            ->issuedBy($teamId)
            ->issuedAt($now)
            ->expiresAt($now->modify('+1 hour'))
            ->withHeader('kid', $keyId)
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }

    /**
     * {@inheritdoc}
     */
    protected function getUserByToken($token)
    {
        // Extract the user info from the ID token
        $claims = explode('.', $token)[1];
        $claims = json_decode(base64_decode($claims), true);

        return $claims;
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        // Get the user from the token claims
        return (new User)->setRaw($user)->map([
            'id' => $user['sub'],
            'email' => $user['email'] ?? null,
            'email_verified' => $user['email_verified'] ?? null,
            'name' => $user['name'] ?? null,
        ]);
    }
}

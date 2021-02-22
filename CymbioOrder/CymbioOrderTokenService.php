<?php

namespace App\Features\CymbioOrder;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use UnexpectedValueException;

/**
 * Class CymbioOrderTokenService
 * @package App\Features\CymbioOrder
 */
class CymbioOrderTokenService
{
    /** @var Client */
    protected Client $client;

    /** @var string */
    protected string $scope;


    /**
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }


    /**
     * @param string $scope
     * @return $this
     */
    public function setScope(string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }


    /**
     * 取得廠商ApiToken
     * @return array
     */
    private function getApiToken(): array
    {
        $url = config('cymbio.' . env('APP_ENV') . '.TOKEN_URL');
        $tokenType = config('cymbio.' . env('APP_ENV') . '.TOKEN_TYPE');
        $clientId = config('cymbio.' . env('APP_ENV') . '.CLIENT_ID');
        $clientSecret = config('cymbio.' . env('APP_ENV') . '.CLIENT_SECRET');

        try {
            $tokenResponse = $this->client->post($url, [
                'form_params' => [
                    'grant_type' => $tokenType,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'scope' => $this->scope,
                ],
            ]);

            $token = json_decode((string)$tokenResponse->getBody(), true);

            if (!isset($token['token_type']) || !isset($token['access_token'])) {
                throw new UnexpectedValueException('未能取得 Token');
            }

            return $token;

        } catch (GuzzleException $ex) {
            throw new UnexpectedValueException('取得 Token 時失敗');
        }
    }

    /**
     * 取得Headers
     * @return array
     */
    public function getHeaders(): array
    {
        $token = $this->getApiToken();

        $token_type = $token['token_type'];
        $access_token = $token['access_token'];

        return [
            "Authorization" => $token_type . ' ' . $access_token,
            "accept" => 'application/json',
            "Content-Type" => "application/json",
        ];
    }
}
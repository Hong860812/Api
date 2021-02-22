<?php


namespace App\Features\Cymbio;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use UnexpectedValueException;

/**
 * Class CymbioApiService
 * @package App\Features\Cymbio
 */
class CymbioApiService
{
    /** @var string */
    public $scope;

    /** @var array */
    public $header;

    /** @var Client */
    public Client $client;

    /** @var string */
    public $url;

    /** @var string */
    public $tokenType;

    /** @var int */
    public $clientId;

    /** @var string */
    public $clientSecret;

    /**
     * CymbioApiService constructor.
     * @param Client $client
     */
    public function __construct(Client $client)
    {
        $this->client       = $client;
        $this->url          = config('cymbio.' . env('APP_ENV') . '.TOKEN_URL');
        $this->tokenType    = config('cymbio.' . env('APP_ENV') . '.TOKEN_TYPE');
        $this->clientId     = config('cymbio.' . env('APP_ENV') . '.CLIENT_ID');
        $this->clientSecret = config('cymbio.' . env('APP_ENV') . '.CLIENT_SECRET');
    }

    /**
     * 建立 token
     * @throws UnexpectedValueException
     */
    public function setHeaderInfo()
    {
        try {
            $tokenInfo    = $this->sendTokenRequest();
            $token        = json_decode((string)$tokenInfo->getBody(), true);
            $tokenTYPE    = $token['token_type'];
            $accessToken  = $token['access_token'];
            $this->header = [
                "Authorization" => $tokenTYPE . ' ' . $accessToken,
                "accept"        => 'application/json',
                "Content-Type"  => "application/json",
            ];
        } catch (GuzzleException $ex) {
            Log::channel('consign')->error('[getJsonFromApi] Service 錯誤', [
                'message' => $ex->getMessage(),
            ]);
            throw new UnexpectedValueException('[getJsonFromApi]header 設定失敗');
        }

    }

    /**
     * @return mixed
     * @throws UnexpectedValueException
     */
    private function sendTokenRequest()
    {
        try {
            return $this->client->post($this->url, [
                'form_params' => [
                    'grant_type'    => $this->tokenType,
                    'client_id'     => $this->clientId,
                    'client_secret' => $this->clientSecret,
                    'scope'         => $this->scope,
                ],
            ]);
        } catch (GuzzleException $ex) {
            Log::channel('consign')->error('[getJsonFromApi] Service 錯誤', [
                'message' => $ex->getMessage(),
            ]);
            throw new UnexpectedValueException('[getJsonFromApi]請求token 失敗');
        }
    }
}
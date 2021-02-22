<?php


namespace App\Features\CymbioOrder\Update;


use App\Features\CymbioOrder\CymbioOrderTokenService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use UnexpectedValueException;

/**
 * Class AbsCymbioOrderUpdateService
 * @package App\Features\CymbioOrder\Update
 */
abstract class AbsCymbioOrderUpdateService
{
    /** @var int */
    const HTTP_SUCCESS = 200;

    /** @var string */
    const SCOPE = 'read:retailers';

    /** @var string[] */
    protected array $header;

    /** @var string */
    protected string $baseURL;

    /** @var int */
    protected int $supplierId;

    /** @var int */
    protected int $retailerId;

    /** @var Client */
    protected Client $client;

    /** @var int */
    protected int $vendorOrderReference;

    /** @var CymbioOrderTokenService */
    private CymbioOrderTokenService $cymbioOrderTokenService;


    /**
     * @return $this
     */
    abstract public function init(): self;

    /**
     * AbsCymbioOrderUpdateService constructor.
     * @param Client $client
     * @param CymbioOrderTokenService $cymbioOrderTokenService
     */
    public function __construct(Client $client, CymbioOrderTokenService $cymbioOrderTokenService)
    {
        $this->client = $client;
        $this->cymbioOrderTokenService = $cymbioOrderTokenService;
    }



    /**
     * @param string $orderReference
     * @return Collection
     */
    public function getfulfillmentsInfo(string $orderReference): Collection
    {
        try {
            $headers = $this->cymbioOrderTokenService->setScope(self::SCOPE)->getHeaders();

            $url = $this->getApiUrl($orderReference);

            $response = $this->client->request('GET', $url, [
                'headers' => $headers,
            ]);

            if (empty($response) || $response->getStatusCode() !== self::HTTP_SUCCESS) {
                throw new UnexpectedValueException('取得 Tracking Info 失敗');
            }

            $orderInfo = collect(json_decode($response->getBody(), true));

            return collect($orderInfo->get('fulfillments'));

        } catch (GuzzleException $ex) {
            throw new UnexpectedValueException($ex->getMessage());
        }
    }

    /**
     * @param string $orderReference
     * @return string
     */
    private function getApiUrl(string $orderReference): string
    {
        $baseURL = config('cymbio.' . env('APP_ENV') . '.BASE_URL');

        return sprintf($baseURL . '/retailers/' . $this->retailerId . '/orders/%s', $orderReference);
    }

}
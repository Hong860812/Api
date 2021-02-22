<?php


namespace App\Features\CymbioOrder\Create;


use App\DTO\ThirdPartyInfoDto;
use App\Entities\Address;
use App\Entities\Order;
use App\Entities\OrderDetail;
use App\Features\CymbioOrder\CymbioOrderTokenService;
use App\Repositories\ThirdPartySupplierRepository;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use UnexpectedValueException;
use App\Transformers\AddressTransformer;
use Illuminate\Support\Facades\Mail;


/**
 * Class AbsCymbioOrderUpdateService
 * @package App\Features\CymbioOrder\Update
 */
abstract class AbsCymbioOrderService
{

    /** @var int */
    const CREATED_STATUS = 201;

    /** @var array */
    const COMPANY_ADDRESS = [
        "firstname" => "Davis",
        "lastname" => "Huang",
        "address1" => "42606 Christy St.",
        "address2" => "",
        "country" => "United States",
        "country_iso" => "US",
        "state" => "California",
        "state_iso" => "CA",
        "city" => "Fremont",
        "postcode" => "94538",
        "phone" => "5105731556",
        "email" => "merch@ifchic.com",
        "company_name" => "IFCHIC",
    ];

    /** @var string */
    const SCOPE = 'write:orders';

    /** @var Client */
    protected Client $client;

    /** @var ThirdPartySupplierRepository */
    protected ThirdPartySupplierRepository $thirdPartySupplierRepo;

    /** @var  Order */
    protected Order $order;

    /** @var array */
    protected array $product;

    /** @var array */
    protected array $shippingAddress;

    /** @var array */
    protected array $billingAddress;

    /** @var int */
    protected int $supplierId;

    /** @var ThirdPartyInfoDto */
    protected ThirdPartyInfoDto $thirdPartyInfo;

    /** @var int */
    protected int $retailerId;

    /** @var array */
    protected array $header;

    /** @var AddressTransformer */
    protected AddressTransformer $addressTransformer;

    /** @var int */
    protected int $vendorSupplierId;

    /** @var string */
    protected string $vendorName;

    /** @var OrderDetail */
    protected OrderDetail $orderDetail;

    /** @var CymbioOrderTokenService */
    protected CymbioOrderTokenService $cymbioOrderTokenService;


    /**
     * AbsCymbioOrderService constructor.
     * @param Client $client
     * @param ThirdPartySupplierRepository $thirdPartySupplierRepo
     * @param AddressTransformer $addressTransformer
     * @param CymbioOrderTokenService $cymbioOrderTokenService
     */
    public function __construct(Client $client, ThirdPartySupplierRepository $thirdPartySupplierRepo, AddressTransformer $addressTransformer, CymbioOrderTokenService $cymbioOrderTokenService)
    {
        $this->client = $client;
        $this->thirdPartySupplierRepo = $thirdPartySupplierRepo;
        $this->addressTransformer = $addressTransformer;
        $this->cymbioOrderTokenService = $cymbioOrderTokenService;
    }

    /**
     * @return $this
     */
    abstract public function init(): self;


    /**
     * 設定訂單細節
     * @param OrderDetail $orderDetail
     * @return $this
     */
    public function setOrderDetail(OrderDetail $orderDetail): self
    {

        $this->orderDetail = $orderDetail;

        return $this;
    }


    /**
     * 設定訂單商品
     * @return $this
     */
    public function setProduct(): self
    {

        $this->product = [
            [
                'sku' => $this->orderDetail->productAttribute()->first()->supplier_reference,
                'supplier_id' => $this->vendorSupplierId,
                'quantity' => $this->orderDetail->product_quantity,
            ],
        ];

        return $this;
    }


    /**
     * 取得運送地址
     * @param array $address
     * @return array
     */
    private function getAddress(array $address): array
    {
        return [
            "first_name" => $address['firstname'],
            "last_name" => $address['lastname'],
            "address1" => $address['address1'],
            "address2" => $address['address2'],
            "country" => $address['country_iso'],
            "state" => $address['state_iso'],
            "city" => $address['city'],
            "zip_code" => $address['postcode'],
            "phone_number" => $address['phone'],
            "email" => $address['email'],
            "company_name" => "",
        ];
    }


    /**
     * 將客戶的地址設為運送地址
     * @return $this
     */
    public function setCustomerToShippingAddress(): self
    {
        $billingAddressForm = $this->getOrderAddressByType($this->order, 'billingAddress', $this->order->shippingAddress);
        $shippingAddressForm = $this->getOrderAddressByType($this->order, 'shippingAddress', $this->order->shippingAddress);

        $this->shippingAddress =
            [
                'billing_address' => $this->getAddress($billingAddressForm),
                "shipping_method_code" => "UPS_GND",
                'shipping_address' => $this->getAddress($shippingAddressForm)
            ];

        return $this;
    }

    /**
     * @param Order $order
     * @return $this
     */
    public function setOrder(Order $order): self
    {
        $this->order = $order;

        return $this;
    }

    /**
     * 設定 supplier 的運送資訊
     * @return $this
     */
    public function setSupplierShippingInfo(): self
    {
        $thirdPartySupplierInfo = $this->thirdPartySupplierRepo->getThirdPartySupplierInfoById($this->supplierId);

        if ($thirdPartySupplierInfo->isEmpty()) {
            throw new UnexpectedValueException('找不到 Third Party Supplier Info.');
        }

        $info = $thirdPartySupplierInfo->first();

        $this->thirdPartyInfo = new ThirdPartyInfoDto([
            'supplierId' => $info->id_supplier,
            'shippingCountry' => json_decode($info->shipping_country),
            'returnCountry' => json_decode($info->return_country),
            'type' => $info->type,
            'warehouse' => $info->warehouse,
            'shippingDay' => $info->shipping_day,
            'usingReturnLabel' => (bool)$info->using_return_label,
            'shippingCountryWarehouse' => json_decode($info->shipping_country_warehouse),
        ]);

        return $this;
    }


    /**
     * 發送訂單請求
     * @return Collection
     */
    public function sendRequest(): Collection
    {
        $headers = $this->cymbioOrderTokenService->setScope(self::SCOPE)->getHeaders();

        $data = $this->getRequestData();
        $url = $this->getApiUrl();

        try {
            $response = $this->client->request('POST', $url, [
                'headers' => $headers,
                'body' => json_encode($data),
            ]);

            return collect(json_decode($response->getBody(), true));

        } catch (GuzzleException $ex) {
            $message = strstr($ex->getMessage(), '"message"');
            $errorMessage = explode(',', $message)[0];
            $this->sendEmail($errorMessage);
            return collect([]);
        }

    }


    /**
     * 取得訂單資料
     * @return array[]
     */
    private function getRequestData(): array
    {
        $reference = $this->order->reference;

        return [
            'retailer_order_id' => $reference,
            'billing_address' => $this->shippingAddress['billing_address'],
            'shipping_method_code' => $this->shippingAddress['shipping_method_code'],
            'shipping_address' => $this->shippingAddress['shipping_address'],
            'order_lines' => $this->product,
        ];
    }


    /**
     * 判斷商品由廠商出貨或公司出貨
     * @param Order $order
     * @param string $label
     * @param Address $customerAddress
     * @return array
     */
    private function getOrderAddressByType(Order $order, string $label, Address $customerAddress): array
    {

        $address = self::COMPANY_ADDRESS;

        $shippingCountry = $this->thirdPartyInfo->shippingCountry;

        if (in_array((int)$customerAddress->id_country, $shippingCountry)) {
            $address = $this->addressTransformer->transform($order->{$label});
            $address['email'] = $order->customer->email;
        }

        return $address;
    }


    /**
     * 訂單建立失敗寄送Email
     * @param string $errorMessage
     */
    private function sendEmail(string $errorMessage): void
    {
        $this->resetConfig();
        $data = $this->getRequestData();
        $products = ['products' => $this->getEmailInformation(),
            'error_message' => $errorMessage];

        Mail::send('mails.cymbio-order-create-failed', $products, function ($message) use ($data) {
            $message->from(env('MAIL_FROM_ADDRESS'), env('MAIL_FROM_NAME'))
                ->to($data['shipping_address']['email'])
                ->cc(config('setting.mail.dropship-cc'))
                ->subject('Cymbio Order Create Failed');
        });
    }


    /**
     * 取得寄送Email的資訊
     * @return array
     */
    private function getEmailInformation(): array
    {
        return [
            'reference' => $this->order->reference,
            'product_name' => $this->orderDetail->product_name,
            'brand' => $this->vendorName,
            'sku' => $this->orderDetail->product_supplier_reference,
            'price' => $this->orderDetail->product_price,
            'quantity' => $this->orderDetail->product_quantity,
        ];
    }


    private function resetConfig(): void
    {
        Config::set([
            'mail.driver' => 'mailgun',
            'mail.host' => env('MAILGUN_HOST'),
            'mail.username' => env('MAILGUN_USERNAME'),
            'mail.password' => env('MAILGUN_PASSWORD'),
        ]);
    }

    /**
     * @return string
     */
    private function getApiUrl(): string
    {
        $baseURL = config('cymbio.' . env('APP_ENV') . '.BASE_URL');
        return $baseURL . '/retailers/' . $this->retailerId . '/orders';
    }
}
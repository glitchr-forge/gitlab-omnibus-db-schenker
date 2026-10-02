<?php

namespace Omnibus\DbSchenker\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\DbSchenker\Api;
use Omnibus\Exception\CarrierException;
use Omnibus\Model\Address;
use Omnibus\Model\Label;
use Omnibus\Request\Request;
use Omnibus\Request\Shipping;

/** A land-transport booking through the Open API (service: a product code, SYSTEM by default); the booking's number is the tracking number. Unverified: built on the published shape. */
final class ShippingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Shipping;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Shipping);
        $s = $request->shipment;
        $data = $this->api->call('POST', '/bookings', array_filter([
            'accountNumber' => $this->api->accountNumber,
            'product' => $s->service ?? 'SYSTEM',
            'reference' => $s->reference,
            'pickupDate' => ($s->shippingDate ?? new \DateTimeImmutable('tomorrow'))->format('Y-m-d'),
            'shipper' => self::party($s->sender),
            'consignee' => self::party($s->recipient),
            'goods' => array_map(static fn ($p) => array_filter(['packageType' => 'CT', 'quantity' => 1, 'weight' => round(max(0.1, $p->weight / 1000), 2), 'length' => $p->length, 'width' => $p->width, 'height' => $p->height, 'description' => $s->option('description', 'Parcel')]), $s->parcels),
        ]));
        $number = (string) ($data['bookingNumber'] ?? $data['shipmentNumber'] ?? $data['id'] ?? '');
        if ('' === $number) {
            throw new CarrierException('db_schenker', 'DB Schenker booked no shipment.');
        }
        $label = $data['labelUrl'] ?? $data['documents'][0]['url'] ?? null;
        $request->setResult(new Label('db_schenker', $number, null, Label::PDF, \is_string($label) ? $label : null, 'https://eschenker.dbschenker.com/app/tracking-public/?refNumber='.rawurlencode($number)));
    }

    private static function party(Address $a): array
    {
        return array_filter(['name' => $a->company ?? $a->name, 'contactName' => $a->name, 'street' => $a->line(0), 'street2' => $a->line(1) ?: null, 'postalCode' => $a->postcode, 'city' => $a->city, 'countryCode' => strtoupper($a->country), 'email' => $a->email, 'phone' => $a->phone]);
    }
}

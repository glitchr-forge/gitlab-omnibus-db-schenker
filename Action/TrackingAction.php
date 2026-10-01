<?php

namespace Omnibus\DbSchenker\Action;

use Omnibus\Action\ActionInterface;
use Omnibus\Action\ApiAwareInterface;
use Omnibus\Action\ApiAwareTrait;
use Omnibus\DbSchenker\Api;
use Omnibus\Model\Tracking as TrackingModel;
use Omnibus\Model\TrackingEvent;
use Omnibus\Model\TrackingStatus;
use Omnibus\Request\Request;
use Omnibus\Request\Tracking;

/** eSchenker's public tracking: the shipment's events, oldest first. */
final class TrackingAction implements ActionInterface, ApiAwareInterface
{
    /** @use ApiAwareTrait<Api> */
    use ApiAwareTrait;

    public function __construct()
    {
        $this->apiClass = Api::class;
    }

    public function supports(Request $request): bool
    {
        return $request instanceof Tracking;
    }

    public function execute(Request $request): void
    {
        \assert($request instanceof Tracking);
        $data = $this->api->track($request->trackingNumber);
        $shipment = $data['shipments'][0] ?? $data['shipment'] ?? $data;
        $events = [];
        foreach ($shipment['events'] ?? $shipment['trackingEvents'] ?? [] as $e) {
            $events[] = new TrackingEvent(new \DateTimeImmutable((string) ($e['eventDate'] ?? $e['date'] ?? $e['timestamp'] ?? 'now')), self::status($e['eventCode'] ?? $e['code'] ?? null, $e['eventDescription'] ?? $e['description'] ?? null), (string) ($e['eventDescription'] ?? $e['description'] ?? ''), trim(implode(' ', array_filter([$e['location']['city'] ?? $e['city'] ?? null, $e['location']['country'] ?? $e['country'] ?? null]))) ?: null, $e['eventCode'] ?? $e['code'] ?? null);
        }
        usort($events, static fn (TrackingEvent $a, TrackingEvent $b) => $a->at <=> $b->at);
        $status = $events ? $events[array_key_last($events)]->status : self::status(null, $shipment['status'] ?? null);
        $request->setResult(new TrackingModel('db-schenker', $request->trackingNumber, $status, $events));
    }

    private static function status(?string $code, ?string $description): TrackingStatus
    {
        $d = strtolower((string) $description);

        return match (true) {
            \in_array($code, ['DLV', 'POD'], true) || str_contains($d, 'delivered') => TrackingStatus::DELIVERED,
            'OFD' === $code || str_contains($d, 'out for delivery') => TrackingStatus::OUT_FOR_DELIVERY,
            str_contains($d, 'return') => TrackingStatus::RETURNED,
            str_contains($d, 'exception') || str_contains($d, 'damage') || str_contains($d, 'refused') || str_contains($d, 'delay') => TrackingStatus::EXCEPTION,
            \in_array($code, ['BKD', 'ORD'], true) || str_contains($d, 'booked') || str_contains($d, 'order received') => TrackingStatus::PENDING,
            null !== $code && '' !== $code || '' !== $d => TrackingStatus::IN_TRANSIT,
            default => TrackingStatus::UNKNOWN,
        };
    }
}

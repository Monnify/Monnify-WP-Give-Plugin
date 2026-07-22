<?php

namespace GiveMonnify\Monnify\Gateway\Actions;

use GiveMonnify\Monnify\Gateway\MonnifyGateway;
use Exception;

class ProcessWebhookNotifications
{
    /**
     * @see https://monnify-docs.playground.monnify.com/docs/webhooks/event-types
     * @throws Exception
     */
    public function __invoke(object $request, MonnifyGateway $gateway)
    {
        if (empty($request->eventType) || empty($request->eventData)) {
            return;
        }

        $eventData = $request->eventData;

        switch ($request->eventType) {
            case 'SUCCESSFUL_TRANSACTION':
                if (empty($eventData->transactionReference)) {
                    return;
                }

                $gateway->webhook->events->donationCompleted($eventData->transactionReference);

                break;
            case 'SUCCESSFUL_REFUND':
                if (empty($eventData->transactionReference)) {
                    return;
                }

                $message = sprintf(
                    __('Donation refunded in Monnify for transaction reference: %s. Monnify refund status: %s', 'give-monnify'),
                    $eventData->transactionReference,
                    $eventData->refundStatus ?? ''
                );

                $gateway->webhook->events->donationRefunded($eventData->transactionReference, $message);

                break;
        }
    }
}

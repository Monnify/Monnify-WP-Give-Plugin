<?php

namespace GiveMonnify\Monnify\Gateway\DataTransferObjects;

class InitializeTransactionResponse
{
    public string $checkoutUrl;

    public string $paymentReference;

    public string $transactionReference;

    public function __construct(array $data)
    {
        $this->checkoutUrl = $data['checkoutUrl'] ?? '';
        $this->paymentReference = $data['paymentReference'] ?? '';
        $this->transactionReference = $data['transactionReference'] ?? '';
    }

    /**
     * Create a new InitializeTransactionResponse from an array
     */
    public static function fromArray(array $data): self
    {
        return new self([
            'checkoutUrl' => $data['checkoutUrl'] ?? '',
            'paymentReference' => $data['paymentReference'] ?? '',
            'transactionReference' => $data['transactionReference'] ?? '',
        ]);
    }
}

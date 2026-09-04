<?php

namespace App\DTOs;

readonly class AddressDTO
{
    public function __construct(
        public ?string $street,
        public ?string $neighborhood,
        public ?string $city,
        public ?string $state,
        public ?string $formattedAddress
    ) {}

    public function toArray(): array
    {
        return [
            'street' => $this->street,
            'neighborhood' => $this->neighborhood,
            'city' => $this->city,
            'state' => $this->state,
            'formatted_address' => $this->formattedAddress,
        ];
    }
}

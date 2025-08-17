<?php

namespace Lpaecomms\Services;

use GuzzleHttp\Client;

class AddressService
{
    private Client $client;
    private string $apiKey = 'fdb9e0567dmsha6e1dfa8a5d4f52p1f769bjsn932503b8f8e9';
    private string $apiHost = 'addressr.p.rapidapi.com';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://addressr.p.rapidapi.com/',
        ]);
    }

    public function getStructuredAddress(string $addressId): ?array
    {
        try {
            $response = $this->client->request('GET', "addresses/{$addressId}", [
                'headers' => [
                    'x-rapidapi-host' => $this->apiHost,
                    'x-rapidapi-key' => $this->apiKey,
                ],
            ]);

            $data = json_decode($response->getBody(), true, 512, JSON_THROW_ON_ERROR);

            return [
                'unitNumber' => $data['structured']['flat']['number'] ?? null,
                'typeApt' => $data['structured']['flat']['type']['name'] ?? null,
                'streetNumberFrom' => $data['structured']['number']['number'] ?? null,
                'streetNumberTo' => $data['structured']['number']['last']['number'] ?? null,
                'streetName' => $data['structured']['street']['name'] ?? null,
                'streetType' => $data['structured']['street']['type']['name'] ?? null,
                'suburb' => $data['structured']['locality']['name'] ?? null,
                'postcode' => $data['structured']['postcode'] ?? null,
                'state' => $data['structured']['state']['abbreviation'] ?? null,
                'sla' => $data['sla'] ?: null,
                'mla' => $data['mla'] ?: null,
                'smla' => $data['smla'] ?: null,
            ];
        } catch (\Exception $e) {
            // Log or handle error
            return null;
        }
    }
}


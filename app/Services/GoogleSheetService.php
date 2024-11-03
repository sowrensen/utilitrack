<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;

class GoogleSheetService
{
    private Client $googleClient;

    private Sheets $service;

    public function __construct(public string $spreadsheetId)
    {
        $this->googleClient = new Client;
        $this->googleClient->setAuthConfig(config('services.google.cloud_config_path'));
        $this->googleClient->addScope([
            Sheets::SPREADSHEETS,
            Sheets::DRIVE,
        ]);

        $this->service = new Sheets($this->googleClient);
    }

    public function readCellValues(string $range): Sheets\ValueRange
    {
        return $this->service->spreadsheets_values->get($this->spreadsheetId, $range);
    }

    public function appendCellValues(array $values, string $range = 'Sheet1'): Sheets\AppendValuesResponse
    {
        $body = new Sheets\ValueRange([
            'values' => $values,
        ]);

        $params = [
            'valueInputOption' => 'USER_ENTERED',
            'insertDataOption' => 'INSERT_ROWS',
        ];

        return $this->service
            ->spreadsheets_values
            ->append($this->spreadsheetId, $range, $body, $params);
    }
}

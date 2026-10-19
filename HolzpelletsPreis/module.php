<?php

declare(strict_types=1);

class HolzpelletsPreis extends IPSModuleStrict
{
    private const URLS = [
        'GER' => 'https://www.heizpellets24.de/api/site/1/prices/history',
        'AT'  => 'https://www.heizpellets24.at/api/site/2/prices/history'
    ];

    private const HISTORY_DAYS = 90;

    private const PRESENTATION_PRICE = [
        'PRESENTATION' => '{3319437D-7CDE-699D-750A-3C6A3841FA75}',
        'DIGITS'       => 2,
        'SUFFIX'       => ' €'
    ];

    private const PRESENTATION_DEMAND = [
        'PRESENTATION' => '{3319437D-7CDE-699D-750A-3C6A3841FA75}',
        'DIGITS'       => 0,
        'SUFFIX'       => ' %'
    ];

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyBoolean('Active', false);
        $this->RegisterPropertyString('Country', 'GER');
        $this->RegisterPropertyInteger('UpdateInterval', 0);
        $this->RegisterPropertyBoolean('UseArchive', false);
        $this->RegisterPropertyInteger('ChartDays', 90);

        $this->RegisterAttributeBoolean('PresentationMigrated', false);
        $this->RegisterAttributeString('History', '[]');
        $this->RegisterVariableFloat('PricePerTon', $this->Translate('Price per ton'), self::PRESENTATION_PRICE, 0);
        $this->RegisterVariableInteger('Demand', $this->Translate('Demand'), self::PRESENTATION_DEMAND, 1);

        $this->RegisterTimer('HolzpelletsPreis_Update', 0, 'HP_Update($_IPS[\'TARGET\']);');

        // Vollbild-Typ gibt es erst ab Symcon 9.1, auf 9.0 fehlt auch die Konstante für den normalen HTML-Typ.
        $this->SetVisualizationType(defined('INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN') ? INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN : 1);
    }

    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges(): void
    {
        //Never delete this line!
        parent::ApplyChanges();

        $this->migratePresentations();

        if ($this->ReadPropertyBoolean('UseArchive')) {
            $this->enableArchiveLogging();
        }

        if (!array_key_exists($this->ReadPropertyString('Country'), self::URLS)) {
            $this->SetTimerInterval('HolzpelletsPreis_Update', 0);
            $this->SetStatus(201);
            return;
        }

        if (!$this->ReadPropertyBoolean('Active')) {
            $this->SetTimerInterval('HolzpelletsPreis_Update', 0);
            $this->SetStatus(104);
            return;
        }

        $this->SetTimerInterval('HolzpelletsPreis_Update', max(0, $this->ReadPropertyInteger('UpdateInterval')) * 3600000);
        $this->SetStatus(102);

        // Beim Systemstart ruft der Kernel ApplyChanges auf, bevor das Netzwerk sicher bereit ist.
        if (IPS_GetKernelRunlevel() === KR_READY) {
            $this->Update();
        }
    }

    public function Update(): void
    {
        $history = $this->getHistory();
        if ($history === []) {
            $this->SetStatus(202);
            return;
        }

        $lastValue = end($history);
        $this->SetStatus(102);
        $this->SetValue('PricePerTon', $lastValue['p']);
        $this->SetValue('Demand', (int) round($lastValue['n']));

        $this->WriteAttributeString('History', json_encode(array_slice($history, -self::HISTORY_DAYS)));
        $this->UpdateVisualizationValue(json_encode($this->getTileData()));
    }

    /**
     * Übernimmt den Preisverlauf der API für Tage, die das Archiv noch nicht kennt. Gibt die Zahl der Werte zurück.
     */
    public function ImportHistory(): int
    {
        $archiveID = $this->getArchiveID();
        $variableID = $this->GetIDForIdent('PricePerTon');
        if ($archiveID === 0 || !AC_GetLoggingStatus($archiveID, $variableID)) {
            $this->LogMessage($this->Translate('Logging of the price is not active. Enable the archive option and apply the changes first.'), KL_WARNING);
            return 0;
        }

        $oldest = time();
        foreach (AC_GetLoggedValues($archiveID, $variableID, 0, time(), 0) as $row) {
            $oldest = min($oldest, $row['TimeStamp']);
        }

        $values = [];
        foreach ($this->getHistory() as $entry) {
            $timestamp = strtotime($entry['d']);
            if ($timestamp !== false && $timestamp < $oldest) {
                $values[] = ['TimeStamp' => $timestamp, 'Value' => $entry['p']];
            }
        }
        if ($values !== []) {
            AC_AddLoggedValues($archiveID, $variableID, $values);
            AC_ReAggregateVariable($archiveID, $variableID);
        }
        return count($values);
    }

    public function GetVisualizationTile(): string
    {
        $html = file_get_contents(__DIR__ . '/module.html');
        return $html . '<script>handleMessage(' . json_encode(json_encode($this->getTileData())) . ');</script>';
    }

    /**
     * Version 2.0: Legacy-Profile (~Euro, ~Intensity.100) werden einmalig durch Darstellungen ersetzt.
     * Nur Variablen, die noch ein Profil tragen, werden angefasst; Anpassungen des Nutzers bleiben.
     */
    private function migratePresentations(): void
    {
        if ($this->ReadAttributeBoolean('PresentationMigrated')) {
            return;
        }

        $variables = [
            'PricePerTon' => [VARIABLETYPE_FLOAT, 'Price per ton', self::PRESENTATION_PRICE, 0],
            'Demand'      => [VARIABLETYPE_INTEGER, 'Demand', self::PRESENTATION_DEMAND, 1]
        ];
        foreach ($variables as $ident => [$type, $name, $presentation, $position]) {
            $id = @$this->GetIDForIdent($ident);
            if ($id && IPS_GetVariable($id)['VariableProfile'] !== '') {
                $this->MaintainVariable($ident, $this->Translate($name), $type, $presentation, $position, true);
            }
        }
        $this->WriteAttributeBoolean('PresentationMigrated', true);
    }

    /**
     * Liefert den Preisverlauf als Liste aus Datum (d), Preis (p) und Nachfrage (n), leer bei Fehlern.
     */
    private function getHistory(): array
    {
        $result = json_decode($this->getPrices(), true);
        if (!is_array($result)) {
            return [];
        }

        $history = [];
        foreach ($result as $entry) {
            if (is_array($entry) && isset($entry['DateTime'], $entry['Price'], $entry['Demand'])) {
                $history[] = [
                    'd' => substr((string) $entry['DateTime'], 0, 10),
                    'p' => (float) $entry['Price'],
                    'n' => (float) $entry['Demand']
                ];
            }
        }
        return $history;
    }

    private function getTileData(): array
    {
        $history = json_decode($this->ReadAttributeString('History'), true);
        if (!is_array($history) || $history === []) {
            return ['texts' => $this->getTileTexts(), 'series' => []];
        }

        $prices = $this->getArchivePrices() ?? array_slice(array_column($history, 'p'), -max(2, $this->ReadPropertyInteger('ChartDays')));
        $count = count($prices);
        return [
            'texts'   => $this->getTileTexts(),
            'price'   => $this->GetValue('PricePerTon'),
            'demand'  => $this->GetValue('Demand'),
            'day'     => $count > 1 ? round($prices[$count - 1] - $prices[$count - 2], 2) : null,
            'week'    => $count > 7 ? round($prices[$count - 1] - $prices[$count - 8], 2) : null,
            'min'     => min($prices),
            'max'     => max($prices),
            'series'  => $prices
        ];
    }

    private function getArchiveID(): int
    {
        $ids = IPS_GetInstanceListByModuleID('{43192F0B-135B-4CE7-A0A7-1475603F3060}');
        return $ids[0] ?? 0;
    }

    private function enableArchiveLogging(): void
    {
        $archiveID = $this->getArchiveID();
        $variableID = $this->GetIDForIdent('PricePerTon');
        if ($archiveID !== 0 && !AC_GetLoggingStatus($archiveID, $variableID)) {
            AC_SetLoggingStatus($archiveID, $variableID, true);
            IPS_ApplyChanges($archiveID);
        }
    }

    /**
     * Tagesmittel des eigenen Archivs für das Diagramm, oder null, wenn das Archiv nicht genutzt wird oder zu wenig Werte hat.
     */
    private function getArchivePrices(): ?array
    {
        $archiveID = $this->getArchiveID();
        if (!$this->ReadPropertyBoolean('UseArchive') || $archiveID === 0) {
            return null;
        }
        $variableID = $this->GetIDForIdent('PricePerTon');
        if (!AC_GetLoggingStatus($archiveID, $variableID)) {
            return null;
        }

        $days = max(2, $this->ReadPropertyInteger('ChartDays'));
        $rows = AC_GetAggregatedValues($archiveID, $variableID, 1, strtotime('today -' . ($days - 1) . ' days'), time(), 0);
        if (!is_array($rows) || count($rows) < 2) {
            return null;
        }
        return array_map(fn (array $row): float => round((float) $row['Avg'], 2), array_reverse($rows));
    }

    private function getTileTexts(): array
    {
        return [
            'price'  => $this->Translate('Price per ton'),
            'demand' => $this->Translate('Demand'),
            'day'    => $this->Translate('vs. yesterday'),
            'week'   => $this->Translate('vs. last week'),
            'low'    => $this->Translate('Low'),
            'high'   => $this->Translate('High'),
            'range'  => $this->Translate('Price in the last %d days'),
            'empty'  => $this->Translate('No price data yet')
        ];
    }

    private function getPrices(): string
    {
        $url = self::URLS[$this->ReadPropertyString('Country')] ?? '';
        if ($url === '') {
            return '';
        }

        $context = stream_context_create([
            'http' => [
                'method'  => 'GET',
                'header'  => 'Accept: application/json',
                'timeout' => 10
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        if ($result === false || !isset($http_response_header[0]) || strpos($http_response_header[0], '200') === false) {
            $this->SendDebug('Update', 'Request to ' . $url . ' failed', 0);
            return '';
        }
        return $result;
    }
}

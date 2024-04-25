<?php

declare(strict_types=1);
    class HolzpelletsPreis extends IPSModuleStrict
    {
        private $URLGER = 'https://www.heizpellets24.de/api/site/1/prices/history';
        private $URLAT = 'https://www.heizpellets24.at/api/site/2/prices/history';

        public function Create(): void
        {
            //Never delete this line!
            parent::Create();
            $this->RegisterPropertyBoolean('Active', false);
            $this->RegisterPropertyString('Country', 'GER');
            $this->RegisterPropertyInteger('UpdateInterval', 0);
            $this->RegisterVariableFloat('PricePerTon', $this->Translate('Price per ton'), '~Euro', 0);
            $this->RegisterVariableInteger('Demand', $this->Translate('Demand'), '~Intensity.100', 1);

            $this->RegisterTimer('HolzpelletsPreis_Update', 0, 'HP_Update($_IPS[\'TARGET\']);');
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

            if ($this->ReadPropertyBoolean('Active')) {
                $this->SetTimerInterval('HolzpelletsPreis_Update', $this->ReadPropertyInteger('UpdateInterval') * 3600000);
                $this->Update();
                $this->SetStatus(102);
            } else {
                $this->SetTimerInterval('HolzpelletsPreis_Update', 0);
                $this->SetStatus(104);
            }
        }

        public function Update(): void
        {
            $lastValue = $this->getLastValue();
            if ($lastValue != null) {
                $this->SetValue('PricePerTon', $lastValue['Price']);
                $this->SetValue('Demand', round($lastValue['Demand'], 2));
            }
        }

        private function getLastValue(): array
        {
            $lastValue = null;
            $result = json_decode($this->getPrices(), true);
            if (count($result) > 0) {
                $lastValue = end($result);
            }
            return $lastValue;
        }

        private function getPrices(): string
        {
            switch ($this->ReadPropertyString('Country')) {
                case 'GER':
                    $URL = $this->URLGER;
                    break;
                case 'AT':
                    $URL = $this->URLAT;
                    break;
                default:
                    return '';

            }
            $opts = [
                'http' => [
                    'method'        => 'GET',
                    'header'        => 'Content-Type: application/json'
                ],
            ];

            $context = stream_context_create($opts);
            $result = file_get_contents($URL, false, $context);
            if ((strpos($http_response_header[0], '200') !== false)) {
                return $result;
            }
            return '';
        }
    }
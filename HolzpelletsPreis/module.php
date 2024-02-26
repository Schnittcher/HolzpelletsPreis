<?php

declare(strict_types=1);
    class HolzpelletsPreis extends IPSModuleStrict
    {
        private $URL = 'https://www.heizpellets24.de/ChartHandler.ashx';

        public function Create(): void
        {
            //Never delete this line!
            parent::Create();
            $this->RegisterPropertyBoolean('Active', false);
            $this->RegisterPropertyInteger('UpdateInterval', 0);
            $this->RegisterVariableFloat('PricePerTon', $this->Translate('Price per ton'), '~Euro', 0);

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
            $lastPrice = $this->getLastPrice();
            if ($lastPrice > 0) {
                $this->SetValue('PricePerTon', $this->getLastPrice());
            }
        }

        private function getLastPrice(): float
        {
            $lastPrice = 0;
            $result = json_decode($this->getPrices(), true);
            if (count($result) > 0) {
                $lastPrice = end($result)['value'];
            }
            return $lastPrice;
        }

        private function getPrices(): string
        {
            $postdata = http_build_query(
                [
                    'ProductId'    => '1',
                    'CountryId'    => '1',
                    'chartMode'    => '3',
                    'defaultRange' => 'true'
                ]
            );
            $opts = [
                'http' => [
                    'method'        => 'POST',
                    'header'        => 'Content-Type: application/x-www-form-urlencoded; charset=UTF-8\r\n',
                    'content'       => $postdata,
                ],
            ];

            $context = stream_context_create($opts);
            $result = file_get_contents($this->URL, false, $context);
            if ((strpos($http_response_header[0], '200') !== false)) {
                return $result;
            }
            return '';
        }
    }
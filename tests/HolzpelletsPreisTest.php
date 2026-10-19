<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

// Der Abruf bei Heizpellets24 (file_get_contents) lässt sich mit den Stubs nicht ersetzen. Alle Instanzen bleiben deshalb
// inaktiv; getestet werden Status, Kachel-Daten und Diagrammquelle mit synthetischem Verlauf im Attribut History.
class HolzpelletsPreisTest extends TestCase
{
    private const MODULE_ID = '{5CF42FDA-9E3B-3BA9-64A6-FBC5C349953D}';
    private const ARCHIVE_ID = '{43192F0B-135B-4CE7-A0A7-1475603F3060}';
    private const STATUS_INACTIVE = 104;
    private const STATUS_INVALID_COUNTRY = 201;

    protected function setUp(): void
    {
        // Jeder Test startet mit einem leeren Kernel und lädt die Bibliotheken neu
        IPS\Kernel::reset();
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/stubs/CoreStubs/library.json');
        parent::setUp();
    }

    // Die Konstanten für den Kachel-Typ gibt es erst ab Symcon 9.1. Die Stubs definieren sie nicht, der Test bildet also
    // ein System ohne sie nach (Symcon 9.0): Die Instanz muss sich trotzdem anlegen lassen.
    public function testInstanceCanBeCreatedOnSystemsWithoutVisualizationConstants(): void
    {
        $this->assertFalse(defined('INSTANCE_VISUALIZATION_TYPE_HTML_FULLSCREEN'));

        $id = $this->instance();

        $this->assertTrue(IPS_InstanceExists($id));
    }

    // --- Konfiguration und Status ---

    public function testNewInstanceHasBothVariablesAndIsInactive(): void
    {
        $id = $this->instance();

        $this->assertSame(VARIABLETYPE_FLOAT, IPS_GetVariable($this->ident($id, 'PricePerTon'))['VariableType']);
        $this->assertSame(VARIABLETYPE_INTEGER, IPS_GetVariable($this->ident($id, 'Demand'))['VariableType']);
        $this->assertSame(self::STATUS_INACTIVE, IPS_GetInstance($id)['InstanceStatus']);
    }

    public function testInvalidCountryIsRejected(): void
    {
        $id = $this->instance();

        IPS_SetProperty($id, 'Country', 'XX');
        IPS_SetProperty($id, 'Active', true);
        IPS_ApplyChanges($id);

        $this->assertSame(self::STATUS_INVALID_COUNTRY, IPS_GetInstance($id)['InstanceStatus']);
    }

    public function testRepeatedApplyChangesKeepsTheSameVariables(): void
    {
        $id = $this->instance();
        $before = IPS_GetChildrenIDs($id);

        IPS_ApplyChanges($id);
        IPS_ApplyChanges($id);

        $this->assertSame($before, IPS_GetChildrenIDs($id));
    }

    // --- Kachel ---

    public function testTileWithoutDataShowsEmptyState(): void
    {
        $data = $this->tileData($this->instance());

        $this->assertSame([], $data['series']);
        $this->assertSame('No price data yet', $data['texts']['empty']);
    }

    public function testTileHtmlContainsStartData(): void
    {
        $html = $this->module($this->instance())->GetVisualizationTile();

        $this->assertStringContainsString('function handleMessage', $html);
        $this->assertStringContainsString('<script>handleMessage(', $html);
    }

    public function testTileCalculatesTrendsAndRangeFromHistory(): void
    {
        $id = $this->instance();
        // Zehn Tage, Preis steigt von 400 auf 409, am letzten Tag aber 405
        $prices = [400.0, 401.0, 402.0, 403.0, 404.0, 405.0, 406.0, 409.0, 410.0, 405.0];
        $this->setHistory($id, $prices);
        SetValue($this->ident($id, 'PricePerTon'), 405.0);
        SetValue($this->ident($id, 'Demand'), 40);

        $data = $this->tileData($id);

        $this->assertEquals($prices, $data['series']);
        $this->assertEquals(405.0, $data['price']);
        $this->assertSame(40, $data['demand']);
        $this->assertEquals(-5.0, $data['day']);
        // Vorwoche: acht Einträge zurück, 402 -> 405
        $this->assertEquals(3.0, $data['week']);
        $this->assertEquals(400.0, $data['min']);
        $this->assertEquals(410.0, $data['max']);
    }

    public function testTileHasNoTrendWithTooFewDays(): void
    {
        $id = $this->instance();
        $this->setHistory($id, [400.0, 401.0, 402.0]);

        $data = $this->tileData($id);

        $this->assertEquals(1.0, $data['day']);
        $this->assertNull($data['week']);
    }

    public function testChartPeriodLimitsTheSeries(): void
    {
        $id = $this->instance();
        $this->setHistory($id, range(400.0, 449.0));

        IPS_SetProperty($id, 'ChartDays', 30);
        IPS_ApplyChanges($id);

        $series = $this->tileData($id)['series'];
        $this->assertCount(30, $series);
        // Es bleiben die neuesten Tage
        $this->assertEquals(449.0, end($series));
    }

    // --- Archiv ---

    public function testEnablingTheArchiveOptionStartsLoggingOfThePrice(): void
    {
        $archive = IPS_CreateInstance(self::ARCHIVE_ID);
        $id = $this->instance();
        $price = $this->ident($id, 'PricePerTon');
        $this->assertFalse(AC_GetLoggingStatus($archive, $price));

        IPS_SetProperty($id, 'UseArchive', true);
        IPS_ApplyChanges($id);

        $this->assertTrue(AC_GetLoggingStatus($archive, $price));
    }

    public function testTileUsesTheArchiveWhenEnabled(): void
    {
        [$id, $archive] = $this->instanceWithArchive();
        $this->setHistory($id, [1.0, 2.0, 3.0]);
        // Das Archiv liefert Tagesmittel mit Rundungsrauschen, jeweils für einen vollen Tag
        $this->addDailyValues($archive, $this->ident($id, 'PricePerTon'), [410.0, 410.0000000001, 411.2999999999]);

        $data = $this->tileData($id);

        $this->assertEquals([410.0, 410.0, 411.3], $data['series']);
        $this->assertEquals(1.3, $data['day']);
    }

    public function testTileFallsBackToApiHistoryWithTooFewArchiveDays(): void
    {
        [$id, $archive] = $this->instanceWithArchive();
        $this->setHistory($id, [400.0, 401.0, 402.0]);
        $this->addDailyValues($archive, $this->ident($id, 'PricePerTon'), [410.0]);

        $this->assertEquals([400.0, 401.0, 402.0], $this->tileData($id)['series']);
    }

    public function testTileIgnoresTheArchiveWhenOptionIsOff(): void
    {
        $archive = IPS_CreateInstance(self::ARCHIVE_ID);
        $id = $this->instance();
        $price = $this->ident($id, 'PricePerTon');
        AC_SetLoggingStatus($archive, $price, true);
        $this->addDailyValues($archive, $price, [410.0, 411.0]);
        $this->setHistory($id, [400.0, 401.0]);

        $this->assertEquals([400.0, 401.0], $this->tileData($id)['series']);
    }

    public function testImportHistoryDoesNothingWithoutActiveLogging(): void
    {
        IPS_CreateInstance(self::ARCHIVE_ID);
        $id = $this->instance();

        $this->assertSame(0, HP_ImportHistory($id));
    }

    // --- Hilfen ---

    private function instance(): int
    {
        $id = IPS_CreateInstance(self::MODULE_ID);
        IPS_ApplyChanges($id);
        return $id;
    }

    private function instanceWithArchive(): array
    {
        $archive = IPS_CreateInstance(self::ARCHIVE_ID);
        $id = IPS_CreateInstance(self::MODULE_ID);
        IPS_SetProperty($id, 'UseArchive', true);
        IPS_ApplyChanges($id);
        return [$id, $archive];
    }

    private function ident(int $instance, string $ident): int
    {
        return IPS_GetObjectIDByIdent($ident, $instance);
    }

    private function module(int $id): object
    {
        return IPS\InstanceManager::getInstanceInterface($id);
    }

    // Schreibt Preise als Verlauf ins Attribut, der letzte Eintrag ist der neueste Tag
    private function setHistory(int $id, array $prices): void
    {
        $history = [];
        foreach (array_values($prices) as $index => $price) {
            $history[] = ['d' => date('Y-m-d', strtotime('today -' . (count($prices) - 1 - $index) . ' days')), 'p' => $price, 'n' => 10.0];
        }
        $module = $this->module($id);
        $method = new ReflectionMethod($module, 'WriteAttributeString');
        $method->invoke($module, 'History', json_encode($history));
    }

    // Trägt Tagesmittel ein: der letzte Wert gehört zu gestern (die Stubs liefern nur abgeschlossene Tage)
    private function addDailyValues(int $archive, int $variable, array $values): void
    {
        $rows = [];
        foreach (array_values($values) as $index => $value) {
            $rows[] = ['TimeStamp' => strtotime('today -' . (count($values) - $index) . ' days'), 'Avg' => $value, 'Min' => $value, 'Max' => $value, 'Duration' => 86400];
        }
        AC_StubsAddAggregatedValues($archive, $variable, 1, $rows);
    }

    private function tileData(int $id): array
    {
        $module = $this->module($id);
        $method = new ReflectionMethod($module, 'getTileData');
        return $method->invoke($module);
    }
}

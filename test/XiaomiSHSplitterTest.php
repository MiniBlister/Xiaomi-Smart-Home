<?php

declare(strict_types = 1);

include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';

use PHPUnit\Framework\TestCase;

class XiaomiSHSplitterTest extends TestCase
{

    private $splitterModuleID = '{66C1E46E-20B6-42FE-8477-2671A0512DD6}';
    private $deviceModuleID = '{B237D1DF-B9B0-4A8D-8EC5-B4F7A88E54FC}';

    public function setUp()
    {
        //Reset
        IPS\Kernel::reset();

        //Register our i/o stubs for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/stubs/IOStubs/library.json');

        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');

        parent::setUp();
    }

    public function testCreate(): void
    {
        $iid = IPS_CreateInstance($this->splitterModuleID);
        $this->assertEquals(count(IPS_GetInstanceListByModuleID($this->splitterModuleID)), 1);
    }

    public function testConfigurationForm(): void
    {
        $iid = IPS_CreateInstance($this->splitterModuleID);
        $form = json_decode(IPS_GetConfigurationForParent($iid), true);

        $this->assertEquals($form, [
            'Password' => ''
        ]);
    }

    public function testReceive(): void
    {
        $splitterID = IPS_CreateInstance($this->splitterModuleID);
        $multicastID = IPS_GetInstance($splitterID)['ConnectionID'];
        IPS_SetProperty($multicastID, 'BindIP', '0.0.0.0');
        IPS_ApplyChanges($multicastID);

        $multicastInterface = IPS\InstanceManager::getInstanceInterface($multicastID);
        $data['DataID'] = '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}';
        $data['Buffer'] = '{"cmd":"heartbeat","model":"gateway","sid":"1022780","short_id":0,"token":"1234567890abcdef","data":"{\"ip\":\"172.22.4.130\"}"}';
        $JSONString = json_encode($data);
        $multicastInterface->ForwardData($JSONString);
    }

}

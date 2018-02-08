<?php

include_once(__DIR__ . "/../XiaomTraits.php");
/**
 * XiaomiDeviceConfigurator Klasse für die Abbildung eines Konfigurators für Xiaomi Devices in IPS.
 * Erweitert IPSModule.
 * 
 */
class XiaomiSmartHomeConfigurator extends ipsmodule
{
    use DebugHelper;
// Überschreibt die interne IPS_Create($id) Funktion
    public function Create()
    {
        parent::Create();
        $this->ConnectParent("{66C1E46E-20B6-42FE-8477-2671A0512DD6}");
        $this->SetReceiveDataFilter('.*"nothingtoreceive":.*');
        $this->RegisterPropertyString("ondevices", "");
    }
// Überschreibt die intere IPS_ApplyChanges($id) Funktion
    public function ApplyChanges()
    {
        parent::ApplyChanges();
        
        //Wenn ein Name geaendert wurde, dann soll der Variablen Namen ebenfalls gaendert werden
        $sensorlist = json_decode($this->ReadPropertyString("ondevices"));
        foreach ($sensorlist as $value) {
        //Instanz darf nicht 0 sein (dann ist der Sensor noch nicht erstellt und auch der Name darf nicht leer sein
            if ($value->instanceID != 0 && $value->name != '')
                IPS_SetName($value->instanceID,$value->name); 
        }
                
    }
    public function ListDevices()
    {
        return $this->Send('get_id_list');
    }
    /**
     * Interne Funktion des SDK.
     *
     * @access public
     */
    public function GetConfigurationForm()
    {
        if ((float) IPS_GetKernelVersion() < 4.2)
            return $this->GetConfigurationForm4_1();
        else
            return $this->GetConfigurationForm4_2();
    }
    private function GetConfigurationForm4_1()
    {
        $FoundDevices = @$this->ListDevices();
        if ($FoundDevices == false)
            $FoundDevices = array();
        $FoundDevices = array_flip($FoundDevices);
        $InstanceIDList = IPS_GetInstanceListByModuleID("{B237D1DF-B9B0-4A8D-8EC5-B4F7A88E54FC}");
        $List = array();
        foreach ($InstanceIDList as $InstanceID)
        {
            $DeviceID = (string) IPS_GetProperty($InstanceID, 'DeviceID');
            if (array_key_exists($DeviceID, $FoundDevices))
            {
                unset($FoundDevices[$DeviceID]);
            }
        }
        foreach ($FoundDevices as $DeviceID => $Value)
        {
            $Device = array(
                'type' => 'Button',
                'label' => $DeviceID,
                'onClick' => 'if (\'' . $DeviceID . '\' == \'\') return; $InstanceID = IPS_CreateInstance(\'{B237D1DF-B9B0-4A8D-8EC5-B4F7A88E54FC}\'); if (IPS_GetInstance($InstanceID)[\'ConnectionID\'] != IPS_GetInstance($id)[\'ConnectionID\']) { if (IPS_GetInstance($InstanceID)[\'ConnectionID\'] > 0) IPS_DisconnectInstance($InstanceID); IPS_ConnectInstance($InstanceID, IPS_GetInstance($id)[\'ConnectionID\']); } @IPS_SetProperty($InstanceID, \'DeviceID\', \'' . $DeviceID . '\'); @IPS_ApplyChanges($InstanceID); IPS_SetName($InstanceID, \'Xiaomi Device\'); IPS_ApplyChanges($id);'
            );
            $List[] = $Device;
        }
        if (count($List) == 0)
            $List[] = array('type' => 'Label', 'label' => 'No new devices found!');
        else
            $List = array_merge(array(array('type' => 'Label', 'label' => 'Hit button to create instance!')), $List);
        $data = array('actions' => $List);
        return json_encode($data);
    }
    private function GetConfigurationForm4_2()
    {
        $FoundDevices = @$this->ListDevices();
        if ($FoundDevices == false)
            $FoundDevices = array();
        $Total = count($FoundDevices);
        $Disconnected = 0;
        $FoundDevices = array_flip($FoundDevices);
        $InstanceIDList = IPS_GetInstanceListByModuleID("{B237D1DF-B9B0-4A8D-8EC5-B4F7A88E54FC}");
        $List = array();
        $MyParent = IPS_GetInstance($this->InstanceID)['ConnectionID'];
        foreach ($InstanceIDList as $InstanceID)
        {
            // Fremde Geräte überspringen
            if (IPS_GetInstance($InstanceID)['ConnectionID'] != $MyParent)
                continue;
            $Device = array(
                'instanceID' => $InstanceID,
                'DeviceID' => (string) IPS_GetProperty($InstanceID, 'DeviceID'),
                'name' => IPS_GetName($InstanceID),
                'type' => IPS_GetObject($InstanceID)['ObjectSummary']
            );
            if (array_key_exists($Device['DeviceID'], $FoundDevices))
            {
                $Device["rowColor"] = "#00ff00";
                unset($FoundDevices[$Device['DeviceID']]);
            }
            else
            {
                $Device["rowColor"] = "#ff0000";
                $Disconnected++;
            }
            $List[] = $Device;
        }
        foreach ($FoundDevices as $DeviceID => $Value)
        {
            $Device = array(
                'instanceID' => 0,
                'DeviceID' => $DeviceID,
                'name' => '',
                'type' => ''
                );
            $List[] = $Device;
        }
        $data = json_decode(file_get_contents(__DIR__ . "/form.json"), true);
        if ($Total > 0)
            $data['actions'][1]['label'] = "Devices found: " . $Total;
        if (count($FoundDevices) > 0)
            $data['actions'][2]['label'] = "New devices: " . count($FoundDevices);
        if ($Disconnected > 0)
            $data['actions'][3]['label'] = "Disconnected devices: " . $Disconnected;
        $data['actions'][4]['values'] = array_merge($data['actions'][4]['values'], $List);
        return json_encode($data);
    }
    private function Send(string $Command)
    {
        $SendData = array("DataID" => "{E496ED12-5963-4494-87F3-E537175E7418}",
            "cmd" => $Command,
            "model" => "gateway");
        $this->SendDebug('Send', $Command, 0);
        $ResultString = @$this->SendDataToParent(json_encode($SendData));
        if ($ResultString === false)
        {
            $this->SendDebug('Receive', 'Error on send command', 0);
            return false;
        }
        $Result = @unserialize($ResultString);
        if (($Result === NULL) or ( $Result === false))
        {
            $this->SendDebug('Receive', 'Error on send command', 0);
            return false;
        }
        $this->SendDebug('Receive', $Result, 0);
        unset($Result['model']);
        return $Result;
    }
}
?>

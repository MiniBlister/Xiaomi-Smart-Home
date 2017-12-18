<?php

include_once(__DIR__ . "/../XiaomTraits.php");

/**
 * XiaomiDevice Klasse für die Abbildung eines Gerätes am Xiaomi Gatewayd in IPS.
 * Erweitert IPSModule.
 * 
 * @property string $model 
 */
class XiaomiSmartHomeDevice extends ipsmodule
{

    use BufferHelper,
        DebugHelper,
        VariableHelper,
        VariableProfile;

    /**
     * Profile der Statusvariablen
     * 
     * @access private
     * @var array 
     */
    static $StatusvarProfile = array(
        "ctrl_neutral1" => array(
            "channel_0" => "~Switch"
        ),
        "ctrl_neutral2" => array(
            "channel_0" => "~Switch",
            "channel_1" => "~Switch"
        ),
        "86sw1" => array(
            "click" => "",
            "double_click" => "",
            "voltage" => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low" => "~Alert"
        ),
        "86sw2" => array(
            "channel_0_click" => "",
            "channel_0_double_click" => "",
            "channel_1_click" => "",
            "channel_1_double_click" => "",
            "dual_channel_both_click" => "",
            "voltage" => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low" => "~Alert"
        ),
        "sensor_ht" => array(
            "temperature" => "~Temperature",
            "humidity" => "~Humidity.F"
        ),
        "rgbw_light" => array(
            "status" => "~Switch",
            "level" => "~Intensity.255"
        ),
        /*         FEHLT inkl. RGB umrechnung für IPS HexColor
          color_temperature	Color Temperature Mired
          x	Current X
          y	Current Y
          saturation	Current Saturation
          hue	Current Hue
         */
        "magnet" => array(
            "status" => "~Window",
            "voltage" => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low" => "~Alert"
        ),
        "motion" => array(
            "status" => "~Motion",
            "lux" => "~Illumination",
            "voltage" => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low" => "~Alert"
        ),
        "plug" => array(
            "status" => "~Switch",
            "load_voltage" => "",
            "load_power" => "",
            "power_consumed" => ""
        ),
        "switch" => array(
            "status_click" => "~Switch",
            "status_double_click" => "~Switch",
            "status_long_click_press" => "~Switch",
            "status_long_click_release" => "~Switch",
            "voltage" => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low" => "~Alert"
        ),
        "weather.v1" => array(
            "pressure" => "~AirPressure.F",
            "temperature" => "~Temperature",
            "humidity" => "~Humidity.F",
            "voltage" => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low" => "~Alert"
        ),
        "sensor_switch.aq2" => array(
            "status_click" => "",
            "status_double_click" => "",
            "voltage" => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low" => "~Alert"
        ),
        "sensor_motion.aq2" => array(
            "status" => "~Motion",
            "lux" => "~Illumination",
            "voltage" => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low" => "~Alert",
            "no_motion" => ""
        ),
        "sensor_magnet.aq2" => array(
            "status" => "~Window",
            "voltage" => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low" => "~Alert",
            "no_close" => ""
        ),
        "sensor_wleak.aq1" => array(
            "status" => "~Alert"
        ),
        "cube" => array(
            "status_shake_air" => "",
            "status_free_fall" => "",
            "status_move" => "",
            "status_tap_twice" => "",
            "status_flip90" => "",
            "status_flip180" => "",
            "status_alert" => "",
            "status_swing" => "",
            "status_iam" => "",
            "rotate" => "",
            "rotate_time" => "",
            "voltage" => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low" => "~Alert"
        ),
        "gateway" => array(
            "rgb" => "~HexColor",
            "brightness" => "~Intensity.255",
            "illumination" => "~Illumination"
        )
    );

    /**
     * Standardaktionen der Statusvariablen
     * 
     * @access private
     * @var array 
     */
    static $StatusvarAction = array(
        "ctrl_neutral1" => array("channel_0"),
        "ctrl_neutral2" => array("channel_0", "channel_1"),
        "rgbw_light" => array("status", "level"),
        /*         FEHLT inkl. RGB umrechnung für IPS HexColor
          color_temperature	Color Temperature Mired
          x	Current X
          y	Current Y
          saturation	Current Saturation
          hue	Current Hue
         */
        "plug" => array("status"),
        "gateway" => array("rgb", "brightness")
    );

// Überschreibt die interne IPS_Create($id) Funktion
    public function Create()
    {
// Diese Zeile nicht löschen.
        parent::Create();
//Always create our own Splitter, when no parent is already available
        $this->ConnectParent("{66C1E46E-20B6-42FE-8477-2671A0512DD6}");
        $this->RegisterPropertyString('DeviceID', "");
        $this->RegisterPropertyInteger('Interval', 0);
        $this->RegisterTimer('RequestState', 0, 'XISMD_RequestState($_IPS[\'TARGET\']);');
        $this->model = "";
    }

// Überschreibt die intere IPS_ApplyChanges($id) Funktion
    public function ApplyChanges()
    {
// Diese Zeile nicht löschen
        parent::ApplyChanges();
//Nur Daten empfangen in ReceiveData für mein Gerät
        $sid = $this->ReadPropertyString("DeviceID");
        $this->SetReceiveDataFilter('(.*\\\"sid\\\":\\\"' . $sid . '\\\".*|.*"STARTUP":"RUN".*)');
// IPS fertig gestartet ?
        if (IPS_GetKernelRunlevel() <> KR_READY)
            return;
        if (trim($sid) == "") // Keine SID, dann kein Timer aktiv
            $this->SetTimerInterval('RequestState', 0);
        else
        {
// Daten anfordern bei Übernehmen
            $this->model = "";
            $this->RequestState();
// Timer an
            $this->SetTimerInterval('RequestState', $this->ReadPropertyInteger('Interval') * 1000);
        }
    }

    public function RequestState()
    {
        $Result = $this->Send('read');
        if ($Result === false)
            return false;
        if (!array_key_exists($this->model, self::$StatusvarProfile))
        {
            $this->SendDebug('unknown model', $this->model, 0);
            return false;
        }
        foreach ($Result as $Ident => $Value)
        {
            $this->Decode($Ident, $Value);
        }
        return true;
    }

    public function RequestAction($Ident, $Value)
    {
        $WriteValue = NULL;
        switch ($this->model)
        {
            case 'ctrl_neutral1':
            case 'ctrl_neutral2':
                $WriteValue = ($Value === true) ? "on" : "off";
                break;
            case 'rgbw_light':
                if ($Ident == "status")
                    $WriteValue = ($Value === true) ? "on" : "off";
                if ($Ident == "level")
                    $WriteValue = (string) $Value;
// Rest fehlt
                break;
            case 'plug':
                if ($Ident == "status")
                    $WriteValue = ($Value === true) ? "on" : "off";
                break;
            case 'gateway':
                $WriteValue = (int) $Value;
            /* case '86sw1':
              case '86sw2':
              case 'sensor_ht':
              case 'magnet':
              case 'motion':
              case 'switch':
              break;
             */
            default:
                echo 'Invalid Ident';
                $WriteValue = null;
                break;
        }
        if ($WriteValue !== NULL)
            $this->WriteValue($Ident, $WriteValue);
    }

    public function WriteValueBoolean(string $Ident, bool $Value)
    {
        if (!is_bool($Value))
        {
            trigger_error('Value must be boolean.', E_USER_NOTICE);
            return false;
        }
        $WriteValue = ($Value === true) ? "on" : "off";
        return $this->WriteValue($Ident, $WriteValue);
    }

    public function WriteValueInteger(string $Ident, int $Value)
    {
        if (!is_integer($Value))
        {
            trigger_error('Value must be integer.', E_USER_NOTICE);
            return false;
        }
        return $this->WriteValue($Ident, (string) $Value);
    }

    public function WriteValueFloat(string $Ident, float $Value)
    {
        if (!is_float($Value))
        {
            trigger_error('Value must be float.', E_USER_NOTICE);
            return false;
        }
        return $this->WriteValue($Ident, (string) $Value);
    }

    public function WriteValueString(string $Ident, string $Value)
    {
        return $this->WriteValue($Ident, (string) $Value);
    }

    private function WriteValue($Ident, $Value)
    {
        if ($this->model == '')
        {
            $this->SendDebug('write error', 'model not set', 0);
            trigger_error('model not set', E_USER_NOTICE);
            return false;
        }
        // Kombiwerte erstellen
        if ($Ident == "rgb")
        {
            $vid = $this->GetStatusVariable("brightness", vtInteger);
            $brightness = round(GetValueInteger($vid) / 2);
            $Value = (($brightness << 24) | $Value);
        }
        if ($Ident == "brightness")
        {
            $vid = $this->GetStatusVariable("rgb", vtInteger);
            $rgb = GetValueInteger($vid);
            $Value = (($Value << 24) | $rgb);
            $Ident = "rgb";
        }
        // Ende Kombiwerte
        $Data[$Ident] = $Value;
        $Result = $this->Send('write', $Data);
        if ($Result === false)
            return false;
        if (!array_key_exists($this->model, self::$StatusvarProfile))
        {
            $this->SendDebug('unknown model', $this->model, 0);
            return false;
        }
        foreach ($Result as $Ident => $Value)
        {
            $this->Decode($Ident, $Value);
        }
        return true;
    }

    public function ReceiveData($JSONString)
    {
        $alldata = json_decode($JSONString);
        if (property_exists($alldata, 'STARTUP'))
        {
            $this->ApplyChanges();
            return;
        }
// Hier kommen nur noch 'report' und 'heartbeat' rein.
        $alldata = json_decode($alldata->Buffer);
        if ($this->model <> trim($alldata->model))
        {
            $this->model = trim($alldata->model);
            $this->SetSummary($this->model);
        }
        $data = json_decode($alldata->data);
        $this->SendDebug("Receive", $data, 0);
        if (!array_key_exists($this->model, self::$StatusvarProfile))
        {
            $this->SendDebug('unknown model', $this->model, 0);
            return;
        }
        foreach ($data as $Ident => $Value)
        {
            $this->Decode($Ident, $Value);
        }
    }

    private function Decode($Ident, $Value)
    {
        $Ident = trim($Ident);
        $this->SendDebug('Decode todo 1: ' . $Ident, $Value, 0);

        if ((!array_key_exists($Ident, self::$StatusvarProfile[$this->model])) && !array_key_exists($Ident . "_" . trim($Value), self::$StatusvarProfile[$this->model]))
        {
            $this->SendDebug('Decode todo 2: ' . $Ident, $Value, 0);
            return;
        }

        if ($Ident == "voltage")
        {
            $this->SetValueFloat("voltage", intval($Value) / 1000);
            $percent = (int) (((int) $Value - 2700) / 4);
            $this->SetValueInteger("voltage_percent", $percent);
            $this->SetValueBoolean("battery_low", ($percent < 20 ? true : false));
            return;
        }

        switch ($this->model)
        {
            case 'ctrl_neutral1':
                return $this->SetValueBoolean($Ident, ($Value == "on") ? true : false);
            case 'ctrl_neutral2':
                return $this->SetValueBoolean($Ident, ($Value == "on") ? true : false);
            case '86sw1':
                return $this->SetValueBoolean(trim($Value), true);
            case '86sw2':
                return $this->SetValueBoolean($Ident . "_" . trim($Value), true);
            case 'sensor_ht':
                return $this->SetValueFloat($Ident, intval($Value) / 100);
            case 'rgbw_light':
                if ($Ident == "status")
                    return $this->SetValueBoolean($Ident, ($Value == "on") ? true : false);
                if ($Ident == "level")
                    return $this->SetValueInteger($Ident, intval($Value));
// if ($Ident == "") // Hue Sat Temp X Y fehlt
                break;
            case 'magnet':
            case 'sensor_magnet.aq2':
                if ($Ident == "status")
                    return $this->SetValueBoolean($Ident, ($Value == "open") ? true : false);
                if ($Ident == "no_close")
                    return $this->SetValueInteger($Ident, (int) $Value);
            case 'motion':
            case 'sensor_motion.aq2':
                if ($Ident == "status")
                    return $this->SetValueBoolean($Ident, ($Value == "motion") ? true : false);
                if ($Ident == "lux")
                    return $this->SetValueInteger($Ident, (int) $Value);
                if ($Ident == "no_motion")
                {
                    $this->SetValueBoolean("status", false);
                    return $this->SetValueInteger($Ident, (int) $Value);
                }
                break;
            case 'plug':
                if ($Ident == "status")
                    return $this->SetValueBoolean($Ident, ($Value == "on") ? true : false);
                if (($Ident == "load_power") || ($Ident == "power_consumed"))
                    return $this->SetValueFloat($Ident, floatval($Value));
                break;
            case 'switch':
                if ($Ident == "status")
                    return $this->SetValueBoolean($Ident . '_' . trim($Value), true);
                break;
            case 'weather.v1':
                if ($Ident == "pressure")
                    return $this->SetValueFloat($Ident, intval($Value) / 100);
                else
                    return $this->SetValueFloat($Ident, intval($Value) / 100);
                break;
            case 'sensor_switch.aq2':
                return $this->SetValueBoolean($Ident . "_" . trim($Value), true);
            case "sensor_wleak.aq1":
                if ($Ident == "status")
                    return $this->SetValueBoolean($Ident, ($Value == "leak") ? true : false);
            case 'cube':
                if ($Ident == "rotate")
                {
                    $this->SetValueInteger("rotate_time", (int) (explode(',', $Value)[1]));
                    return $this->SetValueInteger("rotate", (int) (explode(',', $Value)[0] * 3.6));
                }
                else
                    return $this->SetValueBoolean($Ident . '_' . trim($Value), true);
                break;
            case 'gateway':
                if ($Ident == "rgb")
                {
                    $this->SetValueInteger($Ident, ((int) $Value & 0xffffff));
                    $this->SetValueInteger('brightness', ((int) $Value >> 24) * 2);
                    return;
                }
                break;
        }
    }

    private function Send(string $Command, string $Data = NULL)
    {
        $SendData = array("DataID" => "{E496ED12-5963-4494-87F3-E537175E7418}",
            "cmd" => $Command,
            "sid" => $this->ReadPropertyString('DeviceID'));
        $this->SendDebug('Send cmd', $Command, 0);
        if ($Data !== NULL)
        {
            $SendData["data"] = $Data;
            $this->SendDebug('Send data', $Data, 0);
        }
        if ($this->model !== "")
            $SendData["model"] = $this->model;
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
            echo 'Error on send command';
            return false;
        }
        $this->SendDebug('Receive', $Result, 0);
        if ($this->model <> trim($Result['model']))
        {
            $this->model = trim($Result['model']);
            $this->SetSummary(trim($Result['model']));
        }
        unset($Result['model']);
        return $Result;
    }

}

?>
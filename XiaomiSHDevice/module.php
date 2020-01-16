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
        "ctrl_neutral1"     => array(
            "channel_0" => "~Switch"
        ),
        "ctrl_neutral2"     => array(
            "channel_0" => "~Switch",
            "channel_1" => "~Switch"
        ),
        "86sw1"             => array(
            "click"           => "",
            "double_click"    => "",
            "voltage"         => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low"     => "~Alert"
        ),
        "remote.b186acn01"  => array(
            "channel_0_click"           => "",
            "channel_0_long_click"      => "",
            "channel_0_double_click"    => "",
            "voltage"         => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low"     => "~Alert"
        ),
        "86sw2"             => array(
            "channel_0_click"         => "",
            "channel_0_double_click"  => "",
            "channel_1_click"         => "",
            "channel_1_double_click"  => "",
            "dual_channel_both_click" => "",
            "voltage"                 => "~Volt",
            "voltage_percent"         => "~Battery.100",
            "battery_low"             => "~Alert"
        ),
        "sensor_ht"         => array(
            "temperature" => "~Temperature",
            "humidity"    => "~Humidity.F"
        ),
        "rgbw_light"        => array(
            "status" => "~Switch",
            "level"  => "~Intensity.255"
        ),
        /*         FEHLT inkl. RGB umrechnung für IPS HexColor
          color_temperature	Color Temperature Mired
          x	Current X
          y	Current Y
          saturation	Current Saturation
          hue	Current Hue
         */
        "magnet"            => array(
            "status"          => "~Window",
            "voltage"         => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low"     => "~Alert"
        ),
        "motion"            => array(
            "status"          => "~Motion",
            "lux"             => "~Illumination",
            "voltage"         => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low"     => "~Alert",
            "no_motion"       => ""
        ),
        "plug"              => array(
            "status"         => "~Switch",
            "load_voltage"   => "",
            "load_power"     => "",
            "power_consumed" => ""
        ),
        "switch"            => array(
            "status_click"              => "~Switch",
            "status_double_click"       => "~Switch",
            "status_long_click_press"   => "~Switch",
            "status_long_click_release" => "~Switch",
            "voltage"                   => "~Volt",
            "voltage_percent"           => "~Battery.100",
            "battery_low"               => "~Alert"
        ),
        "weather.v1"        => array(
            "pressure"        => "~AirPressure.F",
            "temperature"     => "~Temperature",
            "humidity"        => "~Humidity.F",
            "voltage"         => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low"     => "~Alert"
        ),
        "sensor_switch.aq2" => array(
            "status_click"        => "",
            "status_double_click" => "",
            "voltage"             => "~Volt",
            "voltage_percent"     => "~Battery.100",
            "battery_low"         => "~Alert"
        ),
        "sensor_motion.aq2" => array(
            "status"          => "~Motion",
            "lux"             => "~Illumination",
            "voltage"         => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low"     => "~Alert",
            "no_motion"       => ""
        ),
        "smoke"             => array(
            "density"         => "~Intensity.255",
            "voltage"         => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low"     => "~Alert",
            "alarm"           => "XISMD.Smoke"
        ),
        "natgas"            => array(
            "voltage"         => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low"     => "~Alert",
            "alarm"           => "XISMD.Gas"
        ),
        "sensor_magnet.aq2" => array(
            "status"          => "~Window",
            "voltage"         => "~Volt",
            "voltage_percent" => "~Battery.100",
            "battery_low"     => "~Alert",
            "no_close"        => ""
        ),
        "sensor_wleak.aq1"  => array(
            "status" => "~Alert"
        ),
        "cube"              => array(
            "status_shake_air" => "",
            "status_free_fall" => "",
            "status_move"      => "",
            "status_tap_twice" => "",
            "status_flip90"    => "",
            "status_flip180"   => "",
            "status_alert"     => "",
            "status_swing"     => "",
            "status_iam"       => "",
            "rotate"           => "",
            "rotate_time"      => "",
            "voltage"          => "~Volt",
            "voltage_percent"  => "~Battery.100",
            "battery_low"      => "~Alert"
        ),
        "vibration"         => array(
            "status_tilt"      => "",
            "status_free_fall" => "",
            "status_vibrate"   => "",
            "bed_activity"     => "",
            "coordination"     => "", // dummy, wird in X Y Z aufgelöst
            "coordination_x"   => "",
            "coordination_y"   => "",
            "coordination_z"   => "",
            "final_tilt_angle" => "",
            "voltage"          => "~Volt",
            "voltage_percent"  => "~Battery.100",
            "battery_low"      => "~Alert"
        ),
        "gateway"           => array(
            "rgb"          => "~HexColor",
            "brightness"   => "~Intensity.100",
            "illumination" => "~Illumination",
            "mid"          => "XISMD.Tones",
            "vol"          => "~Intensity.100"
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
        "rgbw_light"    => array("status", "level"),
        /*         FEHLT inkl. RGB umrechnung für IPS HexColor
          color_temperature	Color Temperature Mired
          x	Current X
          y	Current Y
          saturation	Current Saturation
          hue	Current Hue
         */
        "plug"          => array("status"),
        "gateway"       => array("rgb", "brightness", "mid", "vol")
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

        // Profile für Töne vom Gateway erstellen
        $this->RegisterProfileIntegerEx('XISMD.Smoke', 'Alert', '', '', array(
            array(0, '0', "", -1),
            array(1, '1', "Alarm", 0xff0000),
            array(2, '2', "Testalarm", 0x0000ff)
        ));
        $this->RegisterProfileIntegerEx('XISMD.Gas', 'Alert', '', '', array(
            array(0, '0', "", -1),
            array(1, '1', "Alarm", 0xff0000),
            array(2, '2', "Testalarm", 0x0000ff)
        ));
        $this->RegisterProfileIntegerEx('XISMD.Tones', 'Speaker', '', '', array(
            array(0, '0', "", -1),
            array(1, '1', "", -1),
            array(2, '2', "", -1),
            array(3, '3', "", -1),
            array(4, '4', "", -1),
            array(5, '5', "", -1),
            array(6, '6', "", -1),
            array(7, '7', "", -1),
            array(8, '8', "", -1),
            array(10, '10', "", -1),
            array(11, '11', "", -1),
            array(12, '12', "", -1),
            array(13, '13', "", -1),
            array(20, '20', "", -1),
            array(21, '21', "", -1),
            array(22, '22', "", -1),
            array(23, '23', "", -1),
            array(24, '24', "", -1),
            array(25, '25', "", -1),
            array(26, '26', "", -1),
            array(27, '27', "", -1),
            array(28, '28', "", -1),
            array(29, '29', "", -1),
            array(10000, 'OFF', "", -1),
            array(10001, 'User 1', "", -1),
            array(10002, 'User 2', "", -1),
            array(10003, 'User 3', "", -1),
            array(10004, 'User 4', "", -1),
            array(10005, 'User 5', "", -1),
            array(10006, 'User 6', "", -1),
            array(10007, 'User 7', "", -1),
            array(10008, 'User 8', "", -1),
        ));
// IPS fertig gestartet ?
        if (IPS_GetKernelRunlevel() <> KR_READY)
            return;
        if (trim($sid) == "") // Keine SID, dann kein Timer aktiv
            $this->SetTimerInterval('RequestState', 0);
        else {
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
        if (!array_key_exists($this->model, self::$StatusvarProfile)) {
            $this->SendDebug('unknown model', $this->model, 0);
            return false;
        }
        foreach ($Result as $Ident => $Value) {
            $this->Decode($Ident, $Value);
        }
        return true;
    }

    public function RequestAction($Ident, $Value)
    {
        $WriteValue = NULL;
        switch ($this->model) {
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
                break;
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
        if (!is_bool($Value)) {
            trigger_error('Value must be boolean.', E_USER_NOTICE);
            return false;
        }
        $WriteValue = ($Value === true) ? "on" : "off";
        return $this->WriteValue($Ident, $WriteValue);
    }

    public function WriteValueInteger(string $Ident, int $Value)
    {
        if (!is_integer($Value)) {
            trigger_error('Value must be integer.', E_USER_NOTICE);
            return false;
        }
        return $this->WriteValue($Ident, (int) $Value);
    }

    public function WriteValueFloat(string $Ident, float $Value)
    {
        if (!is_float($Value)) {
            trigger_error('Value must be float.', E_USER_NOTICE);
            return false;
        }
        return $this->WriteValue($Ident, (float) $Value);
    }

    public function WriteValueString(string $Ident, string $Value)
    {
        return $this->WriteValue($Ident, (string) $Value);
    }

    private function WriteValue($Ident, $Value)
    {
        $Data = array();
        if ($this->model == '') {
            $this->SendDebug('write error', 'model not set', 0);
            trigger_error('model not set', E_USER_NOTICE);
            return false;
        }
        // Kombiwerte erstellen
        if ($Ident == "rgb") {
            $vid = $this->GetStatusVariable("brightness", vtInteger);
            $brightness = GetValueInteger($vid);
            $Value = (($brightness << 24) | $Value);
        }
        if ($Ident == "brightness") {
            $vid = $this->GetStatusVariable("rgb", vtInteger);
            $rgb = GetValueInteger($vid);
            $Value = (($Value << 24) | $rgb);
            $Ident = "rgb";
        }
        if ($this->model == 'gateway') {
            switch ($Ident) {
                case 'mid':
                    $this->SetValueInteger($Ident, $Value);
                    $vid = $this->GetStatusVariable('vol', vtInteger);
                    $Data['vol'] = GetValueInteger($vid);
                    break;
                case 'vol':
                    $this->SetValueInteger($Ident, $Value);
                    $vid = $this->GetStatusVariable('mid', vtInteger);
                    $Data['mid'] = GetValueInteger($vid);
                    break;
            }
        }

        // Ende Kombiwerte
        $Data[$Ident] = $Value;
        $Result = $this->Send('write', $Data);
        if ($Result === false)
            return false;

        foreach ($Result as $Ident => $Value) {
            $this->Decode($Ident, $Value);
        }

        return true;
    }

    public function ReceiveData($JSONString)
    {
        $alldata = json_decode($JSONString);
        if (property_exists($alldata, 'STARTUP')) {
            $this->ApplyChanges();
            return;
        }
// Hier kommen nur noch 'report' und 'heartbeat' rein.
        $alldata = json_decode($alldata->Buffer);
        if ($this->model <> trim($alldata->model)) {
            $this->model = trim($alldata->model);
            $this->SetSummary($this->model);
        }
        $data = json_decode($alldata->data);
        $this->SendDebug("Receive", $data, 0);
        if (!array_key_exists($this->model, self::$StatusvarProfile)) {
            $this->SendDebug('unknown model', $this->model, 0);
            return;
        }
        foreach ($data as $Ident => $Value) {
            $this->Decode($Ident, $Value);
        }
    }

    private function Decode($Ident, $Value)
    {
        $Ident = trim($Ident);
        $this->SendDebug('Decode todo 1: ' . $Ident, $Value, 0);

        if ((!array_key_exists($Ident, self::$StatusvarProfile[$this->model])) && !array_key_exists($Ident . "_" . trim($Value), self::$StatusvarProfile[$this->model])) {
            $this->SendDebug('Decode todo 2: ' . $Ident, $Value, 0);
            return;
        }

        if ($Ident == "voltage") {
            $this->SetValueFloat("voltage", intval($Value) / 1000);
            $percent = (int) (((int) $Value - 2700) / 6);
            $this->SetValueInteger("voltage_percent", $percent);
            $this->SetValueBoolean("battery_low", ($percent < 20 ? true : false));
            return;
        }

        switch ($this->model) {
            case 'ctrl_neutral1':
                return $this->SetValueBoolean($Ident, ($Value == "on") ? true : false);
            case 'ctrl_neutral2':
                return $this->SetValueBoolean($Ident, ($Value == "on") ? true : false);
            case '86sw1':
                return $this->SetValueBoolean(trim($Value), true);
            case '86sw2':
            case 'remote.b186acn01':
                return $this->SetValueBoolean($Ident . "_" . trim($Value), true);
            case 'sensor_ht':
                return $this->SetValueFloat($Ident, intval($Value) / 100);
            case 'rgbw_light':
                if ($Ident == "status") {
                    return $this->SetValueBoolean($Ident, ($Value == "on") ? true : false);
                }
                if ($Ident == "level") {
                    return $this->SetValueInteger($Ident, intval($Value));
                }
// if ($Ident == "") // Hue Sat Temp X Y fehlt
                break;
            case 'magnet':
            case 'sensor_magnet.aq2':
                if ($Ident == "status") {
                    return $this->SetValueBoolean($Ident, ($Value == "open") ? true : false);
                }
                if ($Ident == "no_close") {
                    return $this->SetValueInteger($Ident, (int) $Value);
                }
                return;
            case 'motion':
            case 'sensor_motion.aq2':
                if ($Ident == "status") {
                    return $this->SetValueBoolean($Ident, ($Value == "motion") ? true : false);
                }
                if ($Ident == "lux") {
                    return $this->SetValueInteger($Ident, (int) $Value);
                }
                if ($Ident == "no_motion") {
                    $this->SetValueBoolean("status", false);
                    $this->SetValueInteger($Ident, (int) $Value);
                }
                return;
            case 'plug':
                if ($Ident == "status") {
                    return $this->SetValueBoolean($Ident, ($Value == "on") ? true : false);
                }
                if (($Ident == "load_power") || ($Ident == "power_consumed")) {
                    return $this->SetValueFloat($Ident, floatval($Value));
                }
                return;
            case 'switch':
                if ($Ident == "status") {
                    return $this->SetValueBoolean($Ident . '_' . trim($Value), true);
                }
                return;
            case 'weather.v1':
                return $this->SetValueFloat($Ident, intval($Value) / 100);
            case 'smoke':
            case 'natgas':
                return $this->SetValueInteger($Ident, intval($Value));
            case 'sensor_switch.aq2':
                return $this->SetValueBoolean($Ident . "_" . trim($Value), true);
            case "sensor_wleak.aq1":
                if ($Ident == "status") {
                    return $this->SetValueBoolean($Ident, ($Value == "no leak") ? false : true);
                }
                return;
            case 'cube':
                if ($Ident == "rotate") {
                    $this->SetValueInteger("rotate_time", (int) (explode(',', $Value)[1]));
                    return $this->SetValueInteger("rotate", (int) (explode(',', $Value)[0] * 3.6));
                }
                return $this->SetValueBoolean($Ident . '_' . trim($Value), true);
            case 'vibration':
                if ($Ident == "final_tilt_angle") {
                    return $this->SetValueInteger("final_tilt_angle", (int) $Value * 3.6);
                }
                if ($Ident == "bed_activity") {
                    return $this->SetValueInteger($Ident, (int) $Value);
                }
                if ($Ident == "coordination") {
                    $this->SetValueInteger("coordination_x", (int) (explode(',', $Value)[0]));
                    $this->SetValueInteger("coordination_y", (int) (explode(',', $Value)[1]));
                    return $this->SetValueInteger("coordination_z", (int) (explode(',', $Value)[1]));
                }
                return $this->SetValueBoolean($Ident . '_' . trim($Value), true);
            case 'gateway':
                $this->GetStatusVariable('mid', vtInteger);
                $this->GetStatusVariable('vol', vtInteger);

                if ($Ident == "rgb") {
                    $this->SetValueInteger($Ident, ((int) $Value & 0xffffff));
                    $this->SetValueInteger('brightness', ((int) $Value >> 24));
                    return;
                }
                return $this->SetValueInteger($Ident, (int) $Value);
        }
    }

    private function Send(string $Command, $Data = NULL)
    {
        $SendData = array("DataID" => "{E496ED12-5963-4494-87F3-E537175E7418}",
            "cmd"    => $Command,
            "sid"    => $this->ReadPropertyString('DeviceID'));
        $this->SendDebug('Send cmd', $Command, 0);
        if ($Data !== NULL) {
            $SendData["data"] = $Data;
            $this->SendDebug('Send data', $Data, 0);
        }
        if ($this->model !== "")
            $SendData["model"] = $this->model;
        $ResultString = @$this->SendDataToParent(json_encode($SendData));
        if ($ResultString === false) {
            $this->SendDebug('Receive', 'Error on send command', 0);
            return false;
        }
        $Result = @unserialize($ResultString);
        if (($Result === NULL) or ( $Result === false)) {
            $this->SendDebug('Receive', 'Error on send command', 0);
            trigger_error('Error on send command', E_USER_NOTICE);
            return false;
        }
        $this->SendDebug('Receive', $Result, 0);
        if (array_key_exists('error', $Result)) {
            trigger_error($Result['error'], E_USER_NOTICE);
            return false;
        }

        if (array_key_exists('model', $Result)) {
            if ($this->model <> trim($Result['model'])) {
                $this->model = trim($Result['model']);
                $this->SetSummary(trim($Result['model']));
            }
            unset($Result['model']);
        }
        return $Result;
    }

}

?>

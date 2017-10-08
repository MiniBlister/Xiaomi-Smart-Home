<?php

// SendDataToParent (79827379-F36E-4ADA-8A95-5F8D1DC92FA9)
// SendDateToChild (B75DE28A-A29F-4B11-BF9D-5CC758281F38)
include_once(__DIR__ . "/../XiaomTraits.php");

/**
 * bla bla bla Erklärung Doku bla
 * 
 * @property integer $ParentID 
 * @property string $sid
 * @property string $GatewayIP
 * @property array $SendQueue
 * @property string $Buffer
 */
class XiaomiSmartHomeSplitter extends ipsmodule
{

    // use fügt bestimmte Traits dieser Klasse hinzu.
    use BufferHelper, // Enthält die Magische Methoden __get und __set damit wir bequem auf die Instanz-Buffer zugreifen können.
        Semaphore, // Sorgt dafür dass nicht mehrere Threads gleichzeitig auf z.B. den Buffer zugreifen.
        DebugHelper, // Erweitert die SendDebug Methode von IPS um Arrays und Objekte.
        InstanceStatus // Diverse Methoden für die Verwendung im Splitter
    {
        InstanceStatus::MessageSink as IOMessageSink; // MessageSink gibt es sowohl hier in der Klasse, als auch im Trait InstanceStatus. Hier wird für die Methode im Trait ein Alias benannt.
        InstanceStatus::RegisterParent as IORegisterParent; // MessageSink gibt es sowohl hier in der Klasse, als auch im Trait InstanceStatus. Hier wird für die Methode im Trait ein Alias benannt.
    }

    // public $sidmode; // Das geht nicht, da alle Daten flüchtig sind!
    // Mit __set und  __get wird der SetBuffer und GetBuffer genutzt (siehe BufferHelper Trait)
    /**
     * Interne Funktion des SDK.
     * Wird immer ausgeführt wenn IPS startet und wenn eine Instanz neu erstellt wird.
     * @access public
     */
    public function Create()
    {
        // Diese Zeile nicht löschen.
        parent::Create();
        //Always create our own MultiCast I/O, when no parent is already available
        $this->RequireParent("{BAB408E0-0A0F-48C3-B14E-9FB2FA81F66A}");
        $this->RegisterTimer('KeepAlive', 0, 'XISMS_KeepAlive($_IPS[\'TARGET\']);');
        // Alle Instanz-Buffer initialisieren
        $this->sid = "";
        $this->SendQueue = array();
    }

    // Überschreibt die intere IPS_ApplyChanges($id) Funktion
    public function ApplyChanges()
    {
        // Wir wollen wissen wann IPS fertig ist mit dem starten, weil vorher funktioniert der Datenaustausch nicht.
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
        // Wenn sich unserer IO ändert, wollen wir das auch wissen.
        $this->RegisterMessage($this->InstanceID, DM_CONNECT);
        $this->RegisterMessage($this->InstanceID, DM_DISCONNECT);
        parent::ApplyChanges();
        // Wenn Kernel nicht bereit, dann warten... IPS_KERNELSTARTED/KR_READY kommt ja gleich
        if (IPS_GetKernelRunlevel() <> KR_READY)
            return;
        // SendQueue leeren
        $this->SendQueue = array();
        $this->GatewayIP = "";

        // Unseren Parent merken und auf dessen Statusänderungen registrieren.
        $this->RegisterParent();
        if ($this->HasActiveParent())
            $this->IOChangeState(IS_ACTIVE);
    }

    /**
     * Interne Funktion des SDK.
     * Verarbeitet alle Nachrichten auf die wir uns registriert haben.
     * @access public
     */
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        // Zuerst mal den Trait InstanceStatus die Nachtichten verarbeiten lassen:
        $this->IOMessageSink($TimeStamp, $SenderID, $Message, $Data);
        // Und jetzt wir:
        switch ($Message)
        {
            case IPS_KERNELSTARTED: // Nach dem IPS-Start
                $this->KernelReady(); // Sagt alles.
                break;
        }
    }

    /**
     * Wird ausgeführt wenn der Kernel hochgefahren wurde.
     * @access protected
     */
    protected function KernelReady()
    {
        if ($this->HasActiveParent())
            $this->IOChangeState(IS_ACTIVE);
    }

    /**
     * Überschreibt RegisterParent aus dem Trait InstanceStatus
     */
    protected function RegisterParent()
    {
        $this->IORegisterParent();
        if ($this->ParentID > 0)
            $this->GatewayIP = IPS_GetProperty($this->ParentID, 'Host');
        else
            $this->GatewayIP = "";
    }

    /**
     * Wird über den Trait InstanceStatus ausgeführt wenn sich der Status des Parent ändert.
     * Oder wenn sich die Zuordnung zum Parent ändert.
     * @access protected
     * @param int $State Der neue Status des Parent.
     */
    protected function IOChangeState($State)
    {
        if ($State == IS_ACTIVE) // Parent ist Aktiv geworden
        {
            $this->SetTimerInterval('KeepAlive', 60000); // KeepAlive starten
            $this->RefreshAllDevices();
        }
        else // Oh, Parent ist nicht aktiv geworden
        {
            $this->SetStatus(IS_ACTIVE); // Raus aus den Fehlerzustand
            $this->SetTimerInterval('KeepAlive', 0); // Und kein Keep-Alive mehr.
        }
    }

    /**
     * Interne Funktion des SDK.
     * Wird von der Console aufgerufen, wenn 'unser' IO-Parent geöffnet wird.
     * Außerdem nutzen wir sie in Applychanges, da wir dort die Daten zum konfigurieren nutzen.
     * @access public
     */
    public function GetConfigurationForParent()
    {
        $Config['Port'] = 9898;
        $Config['MulticastIP'] = "224.0.0.50";
        $Config['BindPort'] = 9898;
        $Config['EnableBroadcast'] = false;
        $Config['EnableReuseAddress'] = true;
        $Config['EnableLoopback'] = false;
        return json_encode($Config);
    }

    /** Wird vom Timer aufgerufen, sollte NIE passieren, da wir den Timer bei jedem heartbeat zurücksetzen.
     *  Wenn der Timer also auslöst, ist das Gateway offline :(
     */
    public function KeepAlive()
    {
        $this->SetStatus(IS_EBASE + 3);
        $this->SetTimerInterval('KeepAlive', 0); // Und kein Keep-Alive mehr.
    }

    /** Einmal allen verbundenen Devices die Konfig übernehmen, damit sie ihre Stati holen können.
     * 
     */
    protected function RefreshAllDevices()
    {
        $this->SendDataToChildren(json_encode(Array("DataID" => "{B75DE28A-A29F-4B11-BF9D-5CC758281F38}", "STARTUP" => "RUN")));
    }

    /** Aktuell nur zum testen, später wird diese Funktion private und über ForwardData vom Konfigurator ausgeführt.
     * 
     * @return array
     */
    public function GetAllDevices()
    {
        return $this->Send("get_id_list");
    }

    // Von Device kommend, mit Send versenden und die Antwort zurückgeben.
    public function ForwardData($JSONString)
    {
        // Empfangene Daten von der Device Instanz
        $ForwardData = json_decode($JSONString);
        unset($ForwardData->DataID);
        $this->SendDebug('Forward', $ForwardData, 0);
        // Senden
        if (!property_exists($ForwardData, 'sid'))
            $ForwardData->sid = $this->sid;
        if (property_exists($ForwardData, 'model'))
            if (property_exists($ForwardData, 'data'))
                $result = $this->Send($ForwardData->cmd, $ForwardData->sid, $ForwardData->model, $ForwardData->data);
            else
                $result = $this->Send($ForwardData->cmd, $ForwardData->sid, $ForwardData->model);
        else
            $result = $this->Send($ForwardData->cmd, $ForwardData->sid);
        // Antwort (Array) serialisiert zurück an den Child mit return
        return serialize($result);
    }

    /** Versenden
     * 
     * @param type $cmd
     * @param type $sid
     * @param type $model
     * @param type $Data
     * @return boolean | array False im Fehlerfall. Das Array mit 'data' wenn OK
     */
    private function Send($cmd, $sid, $model = null, $Data = null)
    {
        if (IPS_GetInstance($this->InstanceID)['InstanceStatus'] != IS_ACTIVE)
        {
            trigger_error('Instance Xiaomi Gateway-Splitter (' . $this->InstanceID . ') inactiv. ', E_USER_NOTICE);
            return false;
        }
        if (!$this->HasActiveParent())
        {
            trigger_error('Instance Xiaomi Gateway-Splitter (' . $this->InstanceID . ') has no active parent. ', E_USER_NOTICE);
            return false;
        }
        // Einmal ein leeres Array in den Buffer schieben mit Index sid und cmd
        $SendQueue = $this->SendQueue;
        $SendQueue[$sid][$cmd] = array();
        $this->SendQueue = $SendQueue;
        // Daten aufbereiten
        $SendData = array(
            "cmd" => $cmd,
            "sid" => $sid);
        if ($model !== NULL)
            $SendData["model"] = $model;
        if ($Data !== NULL)
            $SendData["data"] = json_encode($Data);
        $this->SendDebug('Send', $SendData, 0);
        try     // versenden
        {
            $this->SendDataToParent(json_encode(Array("DataID" => "{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}", "Buffer" => json_encode($SendData))));
        }
        catch (Exception $exc)
        {
            // oh, Fehler.
            // Den Index wieder entfernene.
            $SendQueue = $this->SendQueue;
            unset($SendQueue[$sid][$cmd]);
            $this->SendQueue = $SendQueue;
            return false;
        }
        // Warten auf Änderung des Buffers durch ReceiveData
        $Result = false;
        for ($x = 0; $x < 500; $x++)
        {
            if (count($this->SendQueue[$sid][$cmd]) > 0)
            { //found 
                $SendQueue = $this->SendQueue;
                $Result = $SendQueue[$sid][$cmd];
                unset($SendQueue[$sid][$cmd]);
                $this->SendQueue = $SendQueue;
                break;
            }
            IPS_Sleep(10);
        }
        $this->SendDebug('Result', $Result, 0);
        return $Result;
    }

    private function UpdateQueue($cmd, $sid, $data, $model)
    {
        if (isset($this->SendQueue[$sid][$cmd]))
        {
            $SendQueue = $this->SendQueue;
            $SendQueue[$sid][$cmd] = json_decode($data, true);
            $SendQueue[$sid][$cmd]['model'] = $model;
            $this->SendQueue = $SendQueue;
        }
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $gateway = json_decode($data->Buffer);
        // Das bei mehr als einem Gateway wir immer alle Nachrichten von allen Gateway bekommen,
        // stört uns nicht. Die Devices filtern über ihre SID und wenn die Anfrage in der SendQueue
        // fehlt war es wohl nicht 'unser' Gateway.
        $this->SendDebug("Receive", json_encode($gateway), 0);
        switch ($gateway->cmd)
        {
            case "heartbeat": //Event ? Dann hier verarbeiten
                // heartbeat vom Gateway mit unerer IP ?
                if (($gateway->model == "gateway") && (json_decode($gateway->data)->ip == $this->GatewayIP))
                {
                    // KeepAlive Timer neustarten
                    $this->SetTimerInterval('KeepAlive', 0);
                    $this->SetStatus(IS_ACTIVE);
                    $this->SetTimerInterval('KeepAlive', 60000);
                    //We need to check IP Address of our Gateway and Update sid accordingly
                    if ($this->sid != $gateway->sid)
                        $this->sid = $gateway->sid;
                }
                else // alle anderen heartbeats an die Childs senden, die finden den Weg über den ReceiveFilter
                    $this->SendDataToChildren(json_encode(Array("DataID" => "{B75DE28A-A29F-4B11-BF9D-5CC758281F38}", "Buffer" => $data->Buffer)));
                break;
            case "write_ack": // Antwort -> Abgleich mit der SendQueue
                $this->UpdateQueue("write", $gateway->sid, $gateway->data, $gateway->model);
                break;
            case "read_ack": // Antwort -> Abgleich mit der SendQueue
                $this->UpdateQueue("read", $gateway->sid, $gateway->data, $gateway->model);
                break;
            case "get_id_list_ack": // Antwort -> Abgleich mit der SendQueue
                // Unsere SID vom Gateway hinzufügen :)
                $Data = json_decode($gateway->data, true);
                $Data[] = $this->sid;
                $this->UpdateQueue("get_id_list", $gateway->sid, json_encode($Data), "gateway");
                break;
            case 'report': // Event für Childs, weitersenden
                $this->SendDataToChildren(json_encode(Array("DataID" => "{B75DE28A-A29F-4B11-BF9D-5CC758281F38}", "Buffer" => $data->Buffer)));
                break;
            default:
                $this->SendDebug('Fehlendes cmd', $gateway->cmd, 0);
                break;
        }
    }

}

?>
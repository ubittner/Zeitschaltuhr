<?php

/**
 * @project       Zeitschaltuhr/Zeitschaltuhr
 * @file          module.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnhandledExceptionInspection */
/** @noinspection PhpUnused */

declare(strict_types=1);

include_once __DIR__ . '/helper/ZSU_autoload.php';

class Zeitschaltuhr extends IPSModule
{
    //Helper
    use ZSU_IsItDay;
    use ZSU_Config;
    use ZSU_ScheduleAction;
    use ZSU_Sunrise;
    use ZSU_Sunset;

    //Constants
    private const LIBRARY_GUID = '{60355992-647F-0632-B9F2-01C93B62ED19}';
    private const MODULE_GUID = '{68424ADC-725A-77E8-F63E-01DA08C6E30F}';
    private const MODULE_PREFIX = 'ZSU';
    private const ABLAUFSTEUERUNG_MODULE_GUID = '{0559B287-1052-A73E-B834-EBD9B62CB938}';
    private const ABLAUFSTEUERUNG_MODULE_PREFIX = 'AST';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        ########## Properties

        ##### Info
        $this->RegisterPropertyString('Note', '');

        ##### Schedule Action
        $this->RegisterPropertyBoolean('UseScheduleAction', false);
        $this->RegisterPropertyInteger('ScheduleAction', 0);
        $this->RegisterPropertyInteger('ScheduleActionToggleActionID1', 0);
        $this->RegisterPropertyInteger('ScheduleActionToggleActionID2', 1);

        ##### Sunrise
        $this->RegisterPropertyBoolean('UseSunrise', false);
        $this->RegisterPropertyInteger('Sunrise', 0);
        $this->RegisterPropertyInteger('SunriseToggleAction', 0);

        ##### Sunset
        $this->RegisterPropertyBoolean('UseSunset', false);
        $this->RegisterPropertyInteger('Sunset', 0);
        $this->RegisterPropertyInteger('SunsetToggleAction', 1);

        ##### Is it day
        $this->RegisterPropertyBoolean('UseIsItDay', false);
        $this->RegisterPropertyInteger('IsItDay', 0);
        $this->RegisterPropertyInteger('IsItDayToggleAction', 0);
        $this->RegisterPropertyInteger('StartOfDay', 0);
        $this->RegisterPropertyInteger('EndOfDay', 0);

        ##### Target
        $this->RegisterPropertyInteger('TargetVariable', 0);

        ##### Command control
        $this->RegisterPropertyInteger('CommandControl', 0);

        ##### Visualisation
        $this->RegisterPropertyBoolean('EnableActive', false);
        $this->RegisterPropertyBoolean('EnableAutomaticMode', true);
        $this->RegisterPropertyBoolean('EnableSwitchingState', true);
        $this->RegisterPropertyBoolean('EnableNextToggleTime', true);

        ########## Variables

        //Active
        $id = @$this->GetIDForIdent('Active');
        $this->RegisterVariableBoolean('Active', 'Aktiv', '~Switch', 10);
        $this->EnableAction('Active');
        if (!$id) {
            $this->SetValue('Active', true);
        }

        //Automatic mode
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.AutomaticMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileIcon($profile, 'Clock');
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', '', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'An', '', 0x00FF00);
        $id = @$this->GetIDForIdent('AutomaticMode');
        $this->RegisterVariableBoolean('AutomaticMode', 'Automatik', $profile, 20);
        $this->EnableAction('AutomaticMode');
        if (!$id) {
            $this->SetValue('AutomaticMode', true);
        }

        //Switching state
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SwitchingState';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileIcon($profile, 'Power');
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', '', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'An', '', 0x00FF00);
        $this->RegisterVariableBoolean('SwitchingState', 'Schaltzustand', $profile, 30);

        //Next toggle time
        $id = @$this->GetIDForIdent('NextToggleTime');
        $this->RegisterVariableString('NextToggleTime', 'Nächster Schaltvorgang', '', 40);
        if (!$id) {
            IPS_SetIcon(@$this->GetIDForIdent('NextToggleTime'), 'Calendar');
        }
    }

    public function ApplyChanges()
    {
        //Wait until IP-Symcon is started
        $this->RegisterMessage(0, IPS_KERNELSTARTED);

        //Never delete this line!
        parent::ApplyChanges();

        //Check runlevel
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all update messages
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
            }
        }

        //Register references and update messages
        //Schedule action
        if ($this->ReadPropertyBoolean('UseScheduleAction')) {
            $id = $this->ReadPropertyInteger('ScheduleAction');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $this->RegisterReference($id);
                $this->RegisterMessage($id, EM_UPDATE);
            }
        }
        //Sunrise
        if ($this->ReadPropertyBoolean('UseSunrise')) {
            $id = $this->ReadPropertyInteger('Sunrise');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $this->RegisterReference($id);
                $this->RegisterMessage($id, VM_UPDATE);
            }
        }
        //Sunset
        if ($this->ReadPropertyBoolean('UseSunset')) {
            $id = $this->ReadPropertyInteger('Sunset');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $this->RegisterReference($id);
                $this->RegisterMessage($id, VM_UPDATE);
            }
        }
        //Is it day
        if ($this->ReadPropertyBoolean('UseIsItDay')) {
            $id = $this->ReadPropertyInteger('IsItDay');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $this->RegisterReference($id);
                $this->RegisterMessage($id, VM_UPDATE);
            }
        }
        //Target variable
        $id = $this->ReadPropertyInteger('TargetVariable');
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $this->RegisterReference($id);
        }

        //WebFront options
        IPS_SetHidden($this->GetIDForIdent('Active'), !$this->ReadPropertyBoolean('EnableActive'));
        IPS_SetHidden($this->GetIDForIdent('AutomaticMode'), !$this->ReadPropertyBoolean('EnableAutomaticMode'));
        IPS_SetHidden($this->GetIDForIdent('SwitchingState'), !$this->ReadPropertyBoolean('EnableSwitchingState'));
        IPS_SetHidden($this->GetIDForIdent('NextToggleTime'), !$this->ReadPropertyBoolean('EnableNextToggleTime'));

        //Reset buffer
        $this->SetBuffer('LastMessage', json_encode([]));

        //Validate configuration
        if (!$this->ValidateConfiguration()) {
            return;
        }

        $this->SetActualState();
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();

        //Delete profiles
        $profiles = ['AutomaticMode', 'SwitchingState'];
        if (!empty($profiles)) {
            foreach ($profiles as $profile) {
                $profileName = self::MODULE_PREFIX . '.' . $this->InstanceID . '.' . $profile;
                $this->UnregisterProfile($profileName);
            }
        }
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        $this->SendDebug('MessageSink', 'Message from SenderID ' . $SenderID . ' with Message ' . $Message . "\r\n Data: " . print_r($Data, true), 0);
        if (!empty($Data)) {
            foreach ($Data as $key => $value) {
                $this->SendDebug(__FUNCTION__, 'Data[' . $key . '] = ' . json_encode($value), 0);
            }
        }

        if (json_decode($this->GetBuffer('LastMessage'), true) === [$SenderID, $Message, $Data]) {
            $this->SendDebug(__FUNCTION__, sprintf(
                'Doppelte Nachricht: Timestamp: %s, SenderID: %s, Message: %s, Data: %s',
                $TimeStamp,
                $SenderID,
                $Message,
                json_encode($Data)
            ), 0);
            return;
        }

        $this->SetBuffer('LastMessage', json_encode([$SenderID, $Message, $Data]));

        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;

            case EM_UPDATE:
                if ($SenderID == $this->ReadPropertyInteger('ScheduleAction')) {
                    if ($Data[1] === false) {
                        break;
                    }
                    $scriptText = self::MODULE_PREFIX . '_ExecuteScheduleAction(' . $this->InstanceID . ');';
                    IPS_RunScriptText($scriptText);
                }
                break;

            case VM_UPDATE:

                //$Data[0] = actual value
                //$Data[1] = value changed
                //$Data[2] = last value
                //$Data[3] = timestamp actual value
                //$Data[4] = timestamp value changed
                //$Data[5] = timestamp last value

                //Sunrise
                if ($SenderID == $this->ReadPropertyInteger('Sunrise') && $Data[1]) { // only on change
                    $scriptText = self::MODULE_PREFIX . '_ExecuteSunriseAction(' . $this->InstanceID . ');';
                    IPS_RunScriptText($scriptText);
                }
                //Sunset
                if ($SenderID == $this->ReadPropertyInteger('Sunset') && $Data[1]) { //only on change
                    $scriptText = self::MODULE_PREFIX . '_ExecuteSunsetAction(' . $this->InstanceID . ');';
                    IPS_RunScriptText($scriptText);
                }
                //Is it day
                if ($SenderID == $this->ReadPropertyInteger('IsItDay') && $Data[1]) { //only on change
                    $scriptText = self::MODULE_PREFIX . '_ExecuteIsItDayAction(' . $this->InstanceID . ');';
                    IPS_RunScriptText($scriptText);
                }
                break;

        }
    }

    public function SetActualState(): void
    {
        $this->SendDebug(__FUNCTION__, 'Der aktuelle Status wird ermittelt.', 0);
        $this->ExecuteScheduleAction();
        $this->CheckSunrise();
        $this->CheckSunset();
        $this->ExecuteIsItDayAction();
        $this->SetNextToggleTime();
    }

    public function CreateEvent(): void
    {
        $id = IPS_CreateEvent(2);
        if (is_int($id)) {
            IPS_SetName($id, 'Ablaufsteuerung');
            echo 'Instanz mit der ID ' . $id . ' wurde erfolgreich erstellt!';
        } else {
            echo 'Instanz konnte nicht erstellt werden!';
        }
    }

    #################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {

            case 'Active':
                $this->SetValue($Ident, $Value);
                break;

            case 'AutomaticMode':
                $this->SetValue($Ident, $Value);
                $this->SetActualState();
                break;
        }
    }

    public function SetNextToggleTime()
    {
        //Reset
        $this->SetValue('NextToggleTime', '');
        //Check automatic mode
        if (!$this->GetValue('AutomaticMode')) {
            return;
        }
        $now = time();
        $timestamps = [];
        //Schedule action
        if ($this->ReadPropertyBoolean('UseScheduleAction')) {
            $id = $this->ReadPropertyInteger('ScheduleAction');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $event = IPS_GetEvent($id);
                $timestamp = $event['NextRun'];
                if ($timestamp > $now) {
                    $interval = ($timestamp - $now) * 1000;
                    $timestamps[] = ['timer' => 'ScheduleAction', 'timestamp' => $timestamp, 'interval' => $interval];
                }
            }
        }
        //Sunrise
        if ($this->ReadPropertyBoolean('UseSunrise')) {
            $id = $this->ReadPropertyInteger('Sunrise');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $timestamp = GetValueInteger($id);
                if ($timestamp > $now) {
                    $interval = ($timestamp - $now) * 1000;
                    $timestamps[] = ['timer' => 'Sunrise', 'timestamp' => $timestamp, 'interval' => $interval];
                }
            }
        }
        //Sunset
        if ($this->ReadPropertyBoolean('UseSunset')) {
            $id = $this->ReadPropertyInteger('Sunset');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $timestamp = GetValueInteger($id);
                if ($timestamp > $now) {
                    $interval = ($timestamp - $now) * 1000;
                    $timestamps[] = ['timer' => 'Sunset', 'timestamp' => $timestamp, 'interval' => $interval];
                }
            }
        }
        //Start of day
        if ($this->ReadPropertyBoolean('UseIsItDay')) {
            $id = $this->ReadPropertyInteger('StartOfDay');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $timestamp = GetValueInteger($id);
                if ($timestamp > $now) {
                    $interval = ($timestamp - $now) * 1000;
                    $timestamps[] = ['timer' => 'Sunset', 'timestamp' => $timestamp, 'interval' => $interval];
                }
            }
        }
        //End of day
        if ($this->ReadPropertyBoolean('UseIsItDay')) {
            $id = $this->ReadPropertyInteger('EndOfDay');
            if ($id != 0 && @IPS_ObjectExists($id)) {
                $timestamp = GetValueInteger($id);
                if ($timestamp > $now) {
                    $interval = ($timestamp - $now) * 1000;
                    $timestamps[] = ['timer' => 'Sunset', 'timestamp' => $timestamp, 'interval' => $interval];
                }
            }
        }
        if (empty($timestamps)) {
            return;
        }
        $this->SendDebug('NextTimer', json_encode($timestamps), 0);
        //Get next timer interval
        $interval = array_column($timestamps, 'interval');
        $min = min($interval);
        $key = array_search($min, $interval);
        $timestamp = $timestamps[$key]['timestamp'];
        $timerInfo = $timestamp + date('Z');
        $date = gmdate('d.m.Y, H:i:s', (integer) $timerInfo);
        $unixTimestamp = strtotime($date);
        $day = date('l', $unixTimestamp);
        switch ($day) {
            case 'Monday':
                $day = 'Montag';
                break;
            case 'Tuesday':
                $day = 'Dienstag';
                break;
            case 'Wednesday':
                $day = 'Mittwoch';
                break;
            case 'Thursday':
                $day = 'Donnerstag';
                break;
            case 'Friday':
                $day = 'Freitag';
                break;
            case 'Saturday':
                $day = 'Samstag';
                break;
            case 'Sunday':
                $day = 'Sonntag';
                break;
        }
        $date = $day . ', ' . $date;
        $this->SetValue('NextToggleTime', $date);
    }

    #################### Private

    private function KernelReady(): void
    {
        $this->ApplyChanges();
    }

    private function UnregisterProfile(string $Name): void
    {
        if (!IPS_VariableProfileExists($Name)) {
            return;
        }
        foreach (IPS_GetVariableList() as $VarID) {
            if (IPS_GetParent($VarID) == $this->InstanceID) {
                continue;
            }
            if (IPS_GetVariable($VarID)['VariableCustomProfile'] == $Name) {
                return;
            }
            if (IPS_GetVariable($VarID)['VariableProfile'] == $Name) {
                return;
            }
        }
        foreach (IPS_GetMediaListByType(MEDIATYPE_CHART) as $mediaID) {
            $content = json_decode(base64_decode(IPS_GetMediaContent($mediaID)), true);
            foreach ($content['axes'] as $axis) {
                if ($axis['profile' === $Name]) {
                    return;
                }
            }
        }
        IPS_DeleteVariableProfile($Name);
    }

    private function ValidateConfiguration(): bool
    {
        $result = true;
        $status = 102;
        //Schedule action
        if ($this->ReadPropertyBoolean('UseScheduleAction')) {
            $id = $this->ReadPropertyInteger('ScheduleAction');
            if ($id == 0 || @!IPS_ObjectExists($id)) {
                $result = false;
                $status = 200;
                $text = 'Abbruch, bitte den zugewiesenen Wochenplan überprüfen!';
                $this->SendDebug(__FUNCTION__, $text, 0);
                $this->LogMessage('ID ' . $this->InstanceID . ', ' . $text, KL_WARNING);
            }
        }
        //Sunrise
        if ($this->ReadPropertyBoolean('UseSunrise')) {
            $id = $this->ReadPropertyInteger('Sunrise');
            if ($id == 0 || @!IPS_ObjectExists($id)) {
                $result = false;
                $status = 200;
                $text = 'Abbruch, bitte den zugewiesenen Sonnenaufgang überprüfen!';
                $this->SendDebug(__FUNCTION__, $text, 0);
                $this->LogMessage('ID ' . $this->InstanceID . ', ' . $text, KL_WARNING);
            }
        }
        //Sunset
        if ($this->ReadPropertyBoolean('UseSunset')) {
            $id = $this->ReadPropertyInteger('Sunset');
            if ($id == 0 || @!IPS_ObjectExists($id)) {
                $result = false;
                $status = 200;
                $text = 'Abbruch, bitte den zugewiesenen Sonnenuntergang überprüfen!';
                $this->SendDebug(__FUNCTION__, $text, 0);
                $this->LogMessage('ID ' . $this->InstanceID . ', ' . $text, KL_WARNING);
            }
        }
        //Is it day
        if ($this->ReadPropertyBoolean('UseIsItDay')) {
            $id = $this->ReadPropertyInteger('IsItDay');
            if ($id == 0 || @!IPS_ObjectExists($id)) {
                $result = false;
                $status = 200;
                $text = 'Abbruch, bitte den zugewiesenen Ist es Tag überprüfen!';
                $this->SendDebug(__FUNCTION__, $text, 0);
                $this->LogMessage('ID ' . $this->InstanceID . ', ' . $text, KL_WARNING);
            }
        }
        //Target variable
        $id = $this->ReadPropertyInteger('TargetVariable');
        if (@!IPS_ObjectExists($id)) {
            $result = false;
            $status = 200;
            $text = 'Abbruch, bitte das zugewiesene Ziel überprüfen!';
            $this->SendDebug(__FUNCTION__, $text, 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . $text, KL_WARNING);
        }
        //Maintenance
        $maintenance = $this->CheckMaintenance();
        if ($maintenance) {
            $result = false;
            $status = 104;
        }
        $this->SetStatus($status);
        return $result;
    }

    private function CheckMaintenance(): bool
    {
        $result = false;
        if (!$this->GetValue('Active')) {
            $this->SendDebug(__FUNCTION__, 'Abbruch, die Instanz ist inaktiv!', 0);
            $result = true;
        }
        return $result;
    }

    private function CheckAutomaticMode(): bool
    {
        $result = boolval($this->GetValue('AutomaticMode'));
        if (!$result) {
            $text = 'Abbruch, die Automatik ist inaktiv!';
            $this->SendDebug(__FUNCTION__, $text, 0);
        }
        return $result;
    }

    private function ToggleState(bool $State): void
    {
        $this->SetValue('SwitchingState', $State);
        $value = 'false';
        if ($State) {
            $value = 'true';
        }
        //Variable
        $id = $this->ReadPropertyInteger('TargetVariable');
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $commandControl = $this->ReadPropertyInteger('CommandControl');
            if ($commandControl > 1 && @IPS_ObjectExists($commandControl)) {
                $commands = [];
                $commands[] = '@RequestAction(' . $id . ', ' . $value . ');';
                $this->SendDebug(__FUNCTION__, 'Befehle: ' . json_encode(json_encode($commands)), 0);
                $scriptText = self::ABLAUFSTEUERUNG_MODULE_PREFIX . '_ExecuteCommands(' . $commandControl . ', ' . json_encode(json_encode($commands)) . ');';
                $this->SendDebug(__FUNCTION__, 'Ablaufsteuerung: ' . $scriptText, 0);
                @IPS_RunScriptText($scriptText);
            } else {
                @RequestAction($id, $State);
            }
        }
        $this->SetNextToggleTime();
    }
}
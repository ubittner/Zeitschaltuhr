<?php

/** @noinspection PhpUnused */

/*
 * @author      Ulrich Bittner
 * @copyright   (c) 2021
 * @license     CC BY-NC-SA 4.0
 * @see         https://github.com/ubittner/Zeitschaltuhr/tree/main/Zeitschaltuhr
 */

declare(strict_types=1);

include_once __DIR__ . '/helper/autoload.php';

class Zeitschaltuhr extends IPSModule
{
    //Helper
    use ZSU_backupRestore;
    use ZSU_isItDay;
    use ZSU_scheduleAction;
    use ZSU_sunrise;
    use ZSU_sunset;

    //Constants
    private const LIBRARY_GUID = '{60355992-647F-0632-B9F2-01C93B62ED19}';
    private const MODULE_NAME = 'Zeitschaltuhr';
    private const MODULE_PREFIX = 'UBZSU';

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        //Properties
        $this->RegisterPropertyBoolean('MaintenanceMode', false);
        $this->RegisterPropertyBoolean('EnableAutomaticMode', true);
        $this->RegisterPropertyBoolean('EnableSwitchingState', true);
        $this->RegisterPropertyBoolean('EnableNextToggleTime', true);
        $this->RegisterPropertyBoolean('UseScheduleAction', false);
        $this->RegisterPropertyInteger('ScheduleAction', 0);
        $this->RegisterPropertyInteger('ScheduleActionToggleActionID1', 0);
        $this->RegisterPropertyInteger('ScheduleActionToggleActionID2', 1);
        $this->RegisterPropertyBoolean('UseSunrise', false);
        $this->RegisterPropertyInteger('Sunrise', 0);
        $this->RegisterPropertyInteger('SunriseToggleAction', 0);
        $this->RegisterPropertyBoolean('UseSunset', false);
        $this->RegisterPropertyInteger('Sunset', 0);
        $this->RegisterPropertyInteger('SunsetToggleAction', 1);
        $this->RegisterPropertyBoolean('UseIsItDay', false);
        $this->RegisterPropertyInteger('IsItDay', 0);
        $this->RegisterPropertyInteger('IsItDayToggleAction', 0);
        $this->RegisterPropertyInteger('StartOfDay', 0);
        $this->RegisterPropertyInteger('EndOfDay', 0);
        $this->RegisterPropertyInteger('TargetVariable', 0);

        //Variables
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.AutomaticMode';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileIcon($profile, 'Clock');
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', '', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'An', '', 0x00FF00);
        $id = @$this->GetIDForIdent('AutomaticMode');
        $this->RegisterVariableBoolean('AutomaticMode', 'Automatik', $profile, 10);
        $this->EnableAction('AutomaticMode');
        if ($id == false) {
            $this->SetValue('AutomaticMode', true);
        }
        $profile = self::MODULE_PREFIX . '.' . $this->InstanceID . '.SwitchingState';
        if (!IPS_VariableProfileExists($profile)) {
            IPS_CreateVariableProfile($profile, 0);
        }
        IPS_SetVariableProfileIcon($profile, 'Power');
        IPS_SetVariableProfileAssociation($profile, 0, 'Aus', '', -1);
        IPS_SetVariableProfileAssociation($profile, 1, 'An', '', 0x00FF00);
        $this->RegisterVariableBoolean('SwitchingState', 'Schaltzustand', $profile, 20);
        $id = @$this->GetIDForIdent('NextToggleTime');
        $this->RegisterVariableString('NextToggleTime', 'Nächster Schaltvorgang', '', 30);
        if ($id == false) {
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

        //Set options
        IPS_SetHidden($this->GetIDForIdent('AutomaticMode'), !$this->ReadPropertyBoolean('EnableAutomaticMode'));
        IPS_SetHidden($this->GetIDForIdent('SwitchingState'), !$this->ReadPropertyBoolean('EnableSwitchingState'));
        IPS_SetHidden($this->GetIDForIdent('NextToggleTime'), !$this->ReadPropertyBoolean('EnableNextToggleTime'));

        //Reset buffer
        $this->SetBuffer('LastMessage', json_encode([]));

        //Delete all references
        foreach ($this->GetReferenceList() as $referenceID) {
            $this->UnregisterReference($referenceID);
        }

        //Delete all registrations
        foreach ($this->GetMessageList() as $senderID => $messages) {
            foreach ($messages as $message) {
                if ($message == VM_UPDATE) {
                    $this->UnregisterMessage($senderID, VM_UPDATE);
                }
                if ($message == EM_UPDATE) {
                    $this->UnregisterMessage($senderID, EM_UPDATE);
                }
            }
        }

        //Validate configuration
        if (!$this->ValidateConfiguration()) {
            return;
        }

        //Add registrations
        if (!$this->CheckMaintenanceMode()) {
            $this->SendDebug(__FUNCTION__, 'Referenzen und Nachrichten werden registriert.', 0);
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
                if (@IPS_VariableProfileExists($profileName)) {
                    IPS_DeleteVariableProfile($profileName);
                }
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

    public function GetConfigurationForm()
    {
        $form = [];

        #################### Elements

        ##### Functions panel

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Funktionen',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'MaintenanceMode',
                    'caption' => 'Wartungsmodus'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableAutomaticMode',
                    'caption' => 'Automatik'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableSwitchingState',
                    'caption' => 'Schaltzustand'
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableNextToggleTime',
                    'caption' => 'Nächster Schaltvorgang'
                ]
            ]
        ];

        ########## Schedule action panel

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Wochenplan',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseScheduleAction',
                    'caption' => 'Wochenplan'
                ],
                [
                    'type'    => 'SelectEvent',
                    'name'    => 'ScheduleAction',
                    'caption' => 'Wochenplan',
                    'width'   => '600px'
                ],
                [
                    'type'    => 'Select',
                    'name'    => 'ScheduleActionToggleActionID1',
                    'caption' => 'Schaltvorgang für die Aktion ID 1',
                    'options' => [
                        [
                            'caption' => 'Ausschalten',
                            'value'   => 0
                        ],
                        [
                            'caption' => 'Einschalten',
                            'value'   => 1
                        ]
                    ]
                ],
                [
                    'type'    => 'Select',
                    'name'    => 'ScheduleActionToggleActionID2',
                    'caption' => 'Schaltvorgang für die Aktion ID 2',
                    'options' => [
                        [
                            'caption' => 'Ausschalten',
                            'value'   => 0
                        ],
                        [
                            'caption' => 'Einschalten',
                            'value'   => 1
                        ]
                    ]
                ]
            ]
        ];

        ########## Sunrise panel

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Sonnenaufgang',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseSunrise',
                    'caption' => 'Sonnenaufgang'
                ],
                [
                    'type'    => 'SelectVariable',
                    'name'    => 'Sunrise',
                    'caption' => 'Sonnenaufgang',
                    'width'   => '600px'
                ],
                [
                    'type'    => 'Select',
                    'name'    => 'SunriseToggleAction',
                    'caption' => 'Schaltvorgang',
                    'options' => [
                        [
                            'caption' => 'Ausschalten',
                            'value'   => 0
                        ],
                        [
                            'caption' => 'Einschalten',
                            'value'   => 1
                        ]
                    ]
                ]
            ]
        ];

        ########## Sunset panel

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Sonnenuntergang',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseSunset',
                    'caption' => 'Sonnenuntergang'
                ],
                [
                    'type'    => 'SelectVariable',
                    'name'    => 'Sunset',
                    'caption' => 'Sonnenuntergang',
                    'width'   => '600px'
                ],
                [
                    'type'    => 'Select',
                    'name'    => 'SunsetToggleAction',
                    'caption' => 'Schaltvorgang',
                    'options' => [
                        [
                            'caption' => 'Ausschalten',
                            'value'   => 0
                        ],
                        [
                            'caption' => 'Einschalten',
                            'value'   => 1
                        ]
                    ]
                ]
            ]
        ];

        ########## Is day panel

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Ist es Tag',
            'items'   => [
                [
                    'type'    => 'CheckBox',
                    'name'    => 'UseIsItDay',
                    'caption' => 'Ist es Tag'
                ],
                [
                    'type'    => 'SelectVariable',
                    'name'    => 'IsItDay',
                    'caption' => 'Ist es Tag',
                    'width'   => '600px'
                ],
                [
                    'type'    => 'Select',
                    'name'    => 'IsItDayToggleAction',
                    'caption' => 'Schaltvorgang',
                    'options' => [
                        [
                            'caption' => 'Ausschalten',
                            'value'   => 0
                        ],
                        [
                            'caption' => 'Einschalten',
                            'value'   => 1
                        ]
                    ]
                ],
                [
                    'type'    => 'SelectVariable',
                    'name'    => 'StartOfDay',
                    'caption' => 'Tagesanfang',
                    'width'   => '600px'
                ],
                [
                    'type'    => 'SelectVariable',
                    'name'    => 'EndOfDay',
                    'caption' => 'Tagesende',
                    'width'   => '600px'
                ]
            ]
        ];

        ########## Target

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Ziel',
            'items'   => [
                [
                    'type'    => 'SelectVariable',
                    'name'    => 'TargetVariable',
                    'caption' => 'Variable',
                    'width'   => '600px'
                ]
            ]
        ];

        #################### Actions

        ##### Configuration panel

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Konfiguration',
            'items'   => [
                [
                    'type'    => 'Button',
                    'caption' => 'Neu einlesen',
                    'onClick' => self::MODULE_PREFIX . '_ReloadConfiguration($id);'
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'SelectCategory',
                            'name'    => 'BackupCategory',
                            'caption' => 'Kategorie',
                            'width'   => '600px'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Sichern',
                            'onClick' => self::MODULE_PREFIX . '_CreateBackup($id, $BackupCategory);'
                        ]
                    ]
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'    => 'SelectScript',
                            'name'    => 'ConfigurationScript',
                            'caption' => 'Konfiguration',
                            'width'   => '600px'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'    => 'PopupButton',
                            'caption' => 'Wiederherstellen',
                            'popup'   => [
                                'caption' => 'Konfiguration wirklich wiederherstellen?',
                                'items'   => [
                                    [
                                        'type'    => 'Button',
                                        'caption' => 'Wiederherstellen',
                                        'onClick' => self::MODULE_PREFIX . '_RestoreConfiguration($id, $ConfigurationScript);'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        ##### Schedule action

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Wochenplan',
            'items'   => [
                [
                    'type'    => 'Button',
                    'caption' => 'Aktuelle Aktion anzeigen',
                    'onClick' => 'UBZSU_ShowActualScheduleAction($id);'
                ]
            ]
        ];

        ##### Test center panel

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Schaltfunktion',
            'items'   => [
                [
                    'type' => 'TestCenter',
                ]
            ]
        ];

        #################### Status

        $library = IPS_GetLibrary(self::LIBRARY_GUID);
        $version = '[Version ' . $library['Version'] . '-' . $library['Build'] . ' vom ' . date('d.m.Y', $library['Date']) . ']';

        $form['status'] = [
            [
                'code'    => 101,
                'icon'    => 'active',
                'caption' => self::MODULE_NAME . ' wird erstellt',
            ],
            [
                'code'    => 102,
                'icon'    => 'active',
                'caption' => self::MODULE_NAME . ' ist aktiv (ID ' . $this->InstanceID . ') ' . $version,
            ],
            [
                'code'    => 103,
                'icon'    => 'active',
                'caption' => self::MODULE_NAME . ' wird gelöscht (ID ' . $this->InstanceID . ') ' . $version,
            ],
            [
                'code'    => 104,
                'icon'    => 'inactive',
                'caption' => self::MODULE_NAME . ' ist inaktiv (ID ' . $this->InstanceID . ') ' . $version,
            ],
            [
                'code'    => 200,
                'icon'    => 'inactive',
                'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug! (ID ' . $this->InstanceID . ') ' . $version
            ]
        ];
        return json_encode($form);
    }

    public function ReloadConfiguration()
    {
        $this->ReloadForm();
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

    #################### Request action

    public function RequestAction($Ident, $Value)
    {
        switch ($Ident) {
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
        //Maintenance mode
        $maintenance = $this->CheckMaintenanceMode();
        if ($maintenance) {
            $result = false;
            $status = 104;
        }
        IPS_SetDisabled($this->InstanceID, $maintenance);
        $this->SetStatus($status);
        return $result;
    }

    private function CheckMaintenanceMode(): bool
    {
        $result = $this->ReadPropertyBoolean('MaintenanceMode');
        if ($result) {
            $text = 'Abbruch, der Wartungsmodus ist aktiv!';
            $this->SendDebug(__FUNCTION__, $text, 0);
            $this->LogMessage('ID ' . $this->InstanceID . ', ' . $text, KL_WARNING);
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
        //Variable
        $id = $this->ReadPropertyInteger('TargetVariable');
        if ($id != 0 && @IPS_ObjectExists($id)) {
            RequestAction($id, $State);
        }
        $this->SetNextToggleTime();
    }
}
<?php

/**
 * @project       Zeitschaltuhr/Zeitschaltuhr
 * @file          ZSU_Config.php
 * @author        Ulrich Bittner
 * @copyright     2022, 2023 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnused */

declare(strict_types=1);

trait ZSU_Config
{
    /**
     * Reloads the configuration form.
     *
     * @return void
     */
    public function ReloadConfig(): void
    {
        $this->ReloadForm();
    }

    /**
     * Modifies a configuration button.
     *
     * @param string $Field
     * @param string $Caption
     * @param int $ObjectID
     * @return void
     */
    public function ModifyButton(string $Field, string $Caption, int $ObjectID): void
    {
        $state = false;
        if ($ObjectID > 1 && @IPS_ObjectExists($ObjectID)) { //0 = main category, 1 = none
            $state = true;
        }
        $this->UpdateFormField($Field, 'caption', $Caption);
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $ObjectID);
    }

    /**
     * Modifies a trigger list configuration button
     *
     * @param string $Field
     * @param string $Condition
     * @return void
     */
    public function ModifyTriggerListButton(string $Field, string $Condition): void
    {
        $id = 0;
        $state = false;
        //Get variable id
        $primaryCondition = json_decode($Condition, true);
        if (array_key_exists(0, $primaryCondition)) {
            if (array_key_exists(0, $primaryCondition[0]['rules']['variable'])) {
                $id = $primaryCondition[0]['rules']['variable'][0]['variableID'];
                if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
                    $state = true;
                }
            }
        }
        $this->UpdateFormField($Field, 'caption', 'ID ' . $id . ' Bearbeiten');
        $this->UpdateFormField($Field, 'visible', $state);
        $this->UpdateFormField($Field, 'objectID', $id);
    }

    /**
     * Gets the configuration form.
     *
     * @return false|string
     * @throws Exception
     */
    public function GetConfigurationForm()
    {
        $form = [];

        ########## Elements

        ##### Element: Info

        $form['elements'][0] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Info',
            'items'   => [
                [
                    'type'    => 'Label',
                    'name'    => 'ModuleID',
                    'caption' => "ID:\t\t\t" . $this->InstanceID
                ],
                [
                    'type'    => 'Label',
                    'name'    => 'ModuleDesignation',
                    'caption' => "Modul:\t\t" . self::MODULE_NAME
                ],
                [
                    'type'    => 'Label',
                    'name'    => 'ModulePrefix',
                    'caption' => "Präfix:\t\t" . self::MODULE_PREFIX
                ],
                [
                    'type'    => 'Label',
                    'name'    => 'ModuleVersion',
                    'caption' => "Version:\t\t" . self::MODULE_VERSION
                ],
                [
                    'type'    => 'Label',
                    'caption' => ' '
                ],
                [
                    'type'    => 'ValidationTextBox',
                    'name'    => 'Note',
                    'caption' => 'Notiz',
                    'width'   => '600px'
                ]
            ]
        ];

        ##### Element: Schedule action

        $id = $this->ReadPropertyInteger('ScheduleAction');
        $enableButton = false;
        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
            $enableButton = true;
        }

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
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectEvent',
                            'name'     => 'ScheduleAction',
                            'caption'  => 'Wochenplan',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "ScheduleActionConfigurationButton", "ID " . $ScheduleAction . "bearbeiten", $ScheduleAction);'
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neuen Wochenplan erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateEvent($id);'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'caption'  => 'ID ' . $id . ' bearbeiten',
                            'name'     => 'ScheduleActionConfigurationButton',
                            'visible'  => $enableButton,
                            'objectID' => $id
                        ]
                    ]
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

        ##### Element: Sunrise

        $sunrise = $this->ReadPropertyInteger('Sunrise');
        $enableSunriseButton = false;
        if ($sunrise > 1 && @IPS_ObjectExists($sunrise)) { //0 = main category, 1 = none
            $enableSunriseButton = true;
        }

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
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'Sunrise',
                            'caption'  => 'Sonnenaufgang',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "SunriseConfigurationButton", "ID " . $Sunrise . " bearbeiten", $Sunrise);'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'SunriseConfigurationButton',
                            'caption'  => 'ID ' . $sunrise . ' bearbeiten',
                            'visible'  => $enableSunriseButton,
                            'objectID' => $sunrise
                        ]
                    ]
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

        ##### Element: Sunset

        $sunset = $this->ReadPropertyInteger('Sunset');
        $enableSunsetButton = false;
        if ($sunset > 1 && @IPS_ObjectExists($sunset)) { //0 = main category, 1 = none
            $enableSunsetButton = true;
        }

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
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'Sunset',
                            'caption'  => 'Sonnenuntergang',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "SunsetConfigurationButton", "ID " . $Sunset . " bearbeiten", $Sunset);'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'SunsetConfigurationButton',
                            'caption'  => 'ID ' . $sunset . ' bearbeiten',
                            'visible'  => $enableSunsetButton,
                            'objectID' => $sunset
                        ]
                    ]
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

        ##### Element: Is day

        $isItDay = $this->ReadPropertyInteger('IsItDay');
        $enableIsItDayButton = false;
        if ($isItDay > 1 && @IPS_ObjectExists($isItDay)) { //0 = main category, 1 = none
            $enableIsItDayButton = true;
        }

        $startOfDay = $this->ReadPropertyInteger('StartOfDay');
        $enableStartOfDayButton = false;
        if ($startOfDay > 1 && @IPS_ObjectExists($startOfDay)) { //0 = main category, 1 = none
            $enableStartOfDayButton = true;
        }

        $endOfDay = $this->ReadPropertyInteger('EndOfDay');
        $enableEndOfDayButton = false;
        if ($endOfDay > 1 && @IPS_ObjectExists($endOfDay)) { //0 = main category, 1 = none
            $enableEndOfDayButton = true;
        }

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
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'IsItDay',
                            'caption'  => 'Ist es Tag',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "IsItDayConfigurationButton", "ID " . $IsItDay . " bearbeiten", $IsItDay);'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'IsItDayConfigurationButton',
                            'caption'  => 'ID ' . $isItDay . ' bearbeiten',
                            'visible'  => $enableIsItDayButton,
                            'objectID' => $isItDay
                        ]
                    ]
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
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'StartOfDay',
                            'caption'  => 'Tagesanfang',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "StartOfDayConfigurationButton", "ID " . $StartOfDay . " bearbeiten", $StartOfDay);'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'StartOfDayConfigurationButton',
                            'caption'  => 'ID ' . $startOfDay . ' bearbeiten',
                            'visible'  => $enableStartOfDayButton,
                            'objectID' => $startOfDay
                        ]
                    ]
                ],
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectVariable',
                            'name'     => 'EndOfDay',
                            'caption'  => 'Tagesanfang',
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "EndOfDayConfigurationButton", "ID " . $EndOfDay . " bearbeiten", $EndOfDay);'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'name'     => 'EndOfDayConfigurationButton',
                            'caption'  => 'ID ' . $endOfDay . ' bearbeiten',
                            'visible'  => $enableEndOfDayButton,
                            'objectID' => $endOfDay
                        ]
                    ]
                ]
            ]
        ];

        ##### Element: Target

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Zielvariable',
            'items'   => [
                [
                    'type'    => 'SelectVariable',
                    'name'    => 'TargetVariable',
                    'caption' => 'Variable',
                    'width'   => '600px'
                ]
            ]
        ];

        ##### Element: Command control

        $id = $this->ReadPropertyInteger('CommandControl');
        $enableButton = false;
        if ($id > 1 && @IPS_ObjectExists($id)) { //0 = main category, 1 = none
            $enableButton = true;
        }
        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Ablaufsteuerung',
            'items'   => [
                [
                    'type'  => 'RowLayout',
                    'items' => [
                        [
                            'type'     => 'SelectModule',
                            'name'     => 'CommandControl',
                            'caption'  => 'Instanz',
                            'moduleID' => self::ABLAUFSTEUERUNG_MODULE_GUID,
                            'width'    => '600px',
                            'onChange' => self::MODULE_PREFIX . '_ModifyButton($id, "CommandControlConfigurationButton", "ID " . $CommandControl . " Instanzkonfiguration", $CommandControl);'
                        ],
                        [
                            'type'    => 'Button',
                            'caption' => 'Neue Instanz erstellen',
                            'onClick' => self::MODULE_PREFIX . '_CreateCommandControlInstance($id);'
                        ],
                        [
                            'type'    => 'Label',
                            'caption' => ' '
                        ],
                        [
                            'type'     => 'OpenObjectButton',
                            'caption'  => 'ID ' . $id . ' Instanzkonfiguration',
                            'name'     => 'CommandControlConfigurationButton',
                            'visible'  => $enableButton,
                            'objectID' => $id
                        ]
                    ]
                ]
            ]
        ];

        ##### Element: Visualisation

        $form['elements'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Visualisierung',
            'items'   => [
                [
                    'type'    => 'Label',
                    'caption' => 'WebFront',
                    'bold'    => true,
                    'italic'  => true
                ],
                [
                    'type'    => 'Label',
                    'caption' => 'Anzeigeoptionen',
                    'italic'  => true
                ],
                [
                    'type'    => 'CheckBox',
                    'name'    => 'EnableActive',
                    'caption' => 'Aktiv'
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

        ########## Actions

        //Reload configuration
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Konfiguration',
            'items'   => [
                [
                    'type'    => 'Button',
                    'caption' => 'Neu laden',
                    'onClick' => self::MODULE_PREFIX . '_ReloadConfig($id);'
                ]
            ]
        ];

        //Test center
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Schaltfunktionen',
            'items'   => [
                [
                    'type' => 'TestCenter',
                ]
            ]
        ];

        //Schedule action
        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Wochenplan',
            'items'   => [
                [
                    'type'    => 'Button',
                    'caption' => 'Aktuelle Aktion anzeigen',
                    'onClick' => 'ZSU_ShowActualScheduleAction($id);'
                ]
            ]
        ];

        //Registered references
        $registeredReferences = [];
        $references = $this->GetReferenceList();
        foreach ($references as $reference) {
            $name = 'Objekt #' . $reference . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($reference)) {
                $name = IPS_GetName($reference);
                $rowColor = '#C0FFC0'; //light green
            }
            $registeredReferences[] = [
                'ObjectID' => $reference,
                'Name'     => $name,
                'rowColor' => $rowColor];
        }

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Registrierte Referenzen',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredReferences',
                    'rowCount' => 10,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredReferencesConfigurationButton", "ID " . $RegisteredReferences["ObjectID"] . " aufrufen", $RegisteredReferences["ObjectID"]);'
                        ]
                    ],
                    'values' => $registeredReferences
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredReferencesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        //Registered messages
        $registeredMessages = [];
        $messages = $this->GetMessageList();
        foreach ($messages as $id => $messageID) {
            $name = 'Objekt #' . $id . ' existiert nicht';
            $rowColor = '#FFC0C0'; //red
            if (@IPS_ObjectExists($id)) {
                $name = IPS_GetName($id);
                $rowColor = '#C0FFC0'; //light green
            }
            switch ($messageID) {
                case [10001]:
                    $messageDescription = 'IPS_KERNELSTARTED';
                    break;

                case [10803]:
                    $messageDescription = 'EM_UPDATE';
                    break;

                case [10603]:
                    $messageDescription = 'VM_UPDATE';
                    break;

                default:
                    $messageDescription = 'keine Bezeichnung';
            }
            $registeredMessages[] = [
                'ObjectID'           => $id,
                'Name'               => $name,
                'MessageID'          => $messageID,
                'MessageDescription' => $messageDescription,
                'rowColor'           => $rowColor];
        }

        $form['actions'][] = [
            'type'    => 'ExpansionPanel',
            'caption' => 'Registrierte Nachrichten',
            'items'   => [
                [
                    'type'     => 'List',
                    'name'     => 'RegisteredMessages',
                    'rowCount' => 10,
                    'sort'     => [
                        'column'    => 'ObjectID',
                        'direction' => 'ascending'
                    ],
                    'columns' => [
                        [
                            'caption' => 'ID',
                            'name'    => 'ObjectID',
                            'width'   => '150px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Name',
                            'name'    => 'Name',
                            'width'   => '300px',
                            'onClick' => self::MODULE_PREFIX . '_ModifyButton($id, "RegisteredMessagesConfigurationButton", "ID " . $RegisteredMessages["ObjectID"] . " aufrufen", $RegisteredMessages["ObjectID"]);'
                        ],
                        [
                            'caption' => 'Nachrichten ID',
                            'name'    => 'MessageID',
                            'width'   => '150px'
                        ],
                        [
                            'caption' => 'Nachrichten Bezeichnung',
                            'name'    => 'MessageDescription',
                            'width'   => '250px'
                        ]
                    ],
                    'values' => $registeredMessages
                ],
                [
                    'type'     => 'OpenObjectButton',
                    'name'     => 'RegisteredMessagesConfigurationButton',
                    'caption'  => 'Aufrufen',
                    'visible'  => false,
                    'objectID' => 0
                ]
            ]
        ];

        ########## Status

        $form['status'][] = [
            'code'    => 101,
            'icon'    => 'active',
            'caption' => self::MODULE_NAME . ' wird erstellt',
        ];
        $form['status'][] = [
            'code'    => 102,
            'icon'    => 'active',
            'caption' => self::MODULE_NAME . ' ist aktiv',
        ];
        $form['status'][] = [
            'code'    => 103,
            'icon'    => 'active',
            'caption' => self::MODULE_NAME . ' wird gelöscht',
        ];
        $form['status'][] = [
            'code'    => 104,
            'icon'    => 'inactive',
            'caption' => self::MODULE_NAME . ' ist inaktiv',
        ];
        $form['status'][] = [
            'code'    => 200,
            'icon'    => 'inactive',
            'caption' => 'Es ist Fehler aufgetreten, weitere Informationen unter Meldungen, im Log oder Debug!',
        ];

        return json_encode($form);
    }
}
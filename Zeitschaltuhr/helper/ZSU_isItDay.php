<?php

/** @noinspection PhpUnused */

/*
 * @author      Ulrich Bittner
 * @copyright   (c) 2021
 * @license     CC BY-NC-SA 4.0
 * @see         https://github.com/ubittner/Zeitschaltuhr/tree/main/Zeitschaltuhr
 */

declare(strict_types=1);

trait ZSU_isItDay
{
    public function ExecuteIsItDayAction(): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if (!$this->CheckAutomaticMode()) {
            return;
        }
        if (!$this->ReadPropertyBoolean('UseIsItDay')) {
            $this->SendDebug(__FUNCTION__, 'Es wird kein Ist es Tag verwendet!', 0);
            return;
        }
        $id = $this->ReadPropertyInteger('IsItDay');
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $this->SendDebug(__FUNCTION__, 'Ist es Tag hat ausgelöst.', 0);
            $toggleAction = !boolval($this->ReadPropertyInteger('IsItDayToggleAction'));
            if (GetValueBoolean($id)) {
                $toggleAction = boolval($this->ReadPropertyInteger('IsItDayToggleAction'));
            }
            $this->ToggleState($toggleAction);
            $this->SetNextToggleTime();
        }
    }
}
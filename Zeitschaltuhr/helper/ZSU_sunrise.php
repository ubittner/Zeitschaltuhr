<?php

/**
 * @project       Zeitschaltuhr/Zeitschaltuhr
 * @file          ZSU_ScheduleAction.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnused */

declare(strict_types=1);

trait ZSU_Sunrise
{
    /**
     * Executes the sunrise action.
     *
     * @return void
     * @throws Exception
     */
    public function ExecuteSunriseAction(): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        if (!$this->CheckAutomaticMode()) {
            return;
        }
        if (!$this->ReadPropertyBoolean('UseSunrise')) {
            $this->SendDebug(__FUNCTION__, 'Es wird kein Sonnenaufgang verwendet!', 0);
            return;
        }
        $id = $this->ReadPropertyInteger('Sunrise');
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $this->SendDebug(__FUNCTION__, 'Der Sonnenaufgang hat ausgelöst.', 0);
            $state = boolval($this->ReadPropertyInteger('SunriseToggleAction'));
            $this->ToggleState($state);
        }
    }

    #################### Private

    /**
     * Checks the sunrise
     *
     * @return void
     * @throws Exception
     */
    private function CheckSunrise(): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        if (!$this->CheckAutomaticMode()) {
            return;
        }
        if (!$this->ReadPropertyBoolean('UseSunrise')) {
            $this->SendDebug(__FUNCTION__, 'Es wird kein Sonnenaufgang verwendet!', 0);
            return;
        }
        $this->SendDebug(__FUNCTION__, 'Es wird geprüft, ob es Sonnenaufgang ist', 0);
        $now = time();
        $sunriseID = $this->ReadPropertyInteger('Sunrise');
        if ($sunriseID != 0 && @IPS_ObjectExists($sunriseID)) {
            if ($this->ReadPropertyBoolean('UseSunset')) {
                $sunsetID = $this->ReadPropertyInteger('Sunset');
                if ($sunsetID != 0 && @IPS_ObjectExists($sunsetID)) {
                    $sunriseTime = GetValueInteger($sunriseID);
                    $sunsetTime = GetValueInteger($sunsetID);
                    $sunrise = $sunriseTime - $now;
                    $sunset = $sunsetTime - $now;
                    if ($sunset < $sunrise) {
                        $this->SendDebug(__FUNCTION__, 'Es ist Sonnenaufgang.', 0);
                        $this->ExecuteSunriseAction();
                    }
                }
            }
        }
    }
}
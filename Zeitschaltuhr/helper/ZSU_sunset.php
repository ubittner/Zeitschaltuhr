<?php

/**
 * @project       Zeitschaltuhr/Zeitschaltuhr
 * @file          ZSU_Sunset.php
 * @author        Ulrich Bittner
 * @copyright     2022 Ulrich Bittner
 * @license       https://creativecommons.org/licenses/by-nc-sa/4.0/ CC BY-NC-SA 4.0
 */

/** @noinspection PhpUnused */

declare(strict_types=1);

trait ZSU_Sunset
{
    /**
     * Executes the sunset action.
     *
     * @return void
     * @throws Exception
     */
    public function ExecuteSunsetAction(): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        if (!$this->CheckAutomaticMode()) {
            return;
        }
        if (!$this->ReadPropertyBoolean('UseSunset')) {
            $this->SendDebug(__FUNCTION__, 'Es wird kein Sonnenuntergang verwendet!', 0);
            return;
        }
        $id = $this->ReadPropertyInteger('Sunset');
        if ($id != 0 && @IPS_ObjectExists($id)) {
            $this->SendDebug(__FUNCTION__, 'Der Sonnenuntergang hat ausgelöst.', 0);
            $state = boolval($this->ReadPropertyInteger('SunsetToggleAction'));
            $this->ToggleState($state);
        }
    }

    #################### Private

    /**
     * Checks the sunset.
     *
     * @return void
     * @throws Exception
     */
    private function CheckSunset(): void
    {
        if ($this->CheckMaintenance()) {
            return;
        }
        if (!$this->CheckAutomaticMode()) {
            return;
        }
        if (!$this->ReadPropertyBoolean('UseSunset')) {
            $this->SendDebug(__FUNCTION__, 'Es wird kein Sonnenuntergang verwendet!', 0);
            return;
        }
        $this->SendDebug(__FUNCTION__, 'Es wird geprüft, ob es Sonnenuntergang ist', 0);
        $now = time();
        $sunsetID = $this->ReadPropertyInteger('Sunset');
        if ($sunsetID != 0 && @IPS_ObjectExists($sunsetID)) {
            if ($this->ReadPropertyBoolean('UseSunrise')) {
                $sunriseID = $this->ReadPropertyInteger('Sunrise');
                if ($sunriseID != 0 && @IPS_ObjectExists($sunriseID)) {
                    $sunriseTime = GetValueInteger($sunriseID);
                    $sunsetTime = GetValueInteger($sunsetID);
                    $sunrise = $sunriseTime - $now;
                    $sunset = $sunsetTime - $now;
                    if ($sunrise < $sunset) {
                        $this->SendDebug(__FUNCTION__, 'Es ist Sonnenuntergang.', 0);
                        $this->ExecuteSunsetAction();
                    }
                }
            }
        }
    }
}
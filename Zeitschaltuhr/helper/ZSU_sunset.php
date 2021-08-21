<?php

/** @noinspection PhpUnused */

/*
 * @author      Ulrich Bittner
 * @copyright   (c) 2021
 * @license     CC BY-NC-SA 4.0
 * @see         https://github.com/ubittner/Zeitschaltuhr/tree/main/Zeitschaltuhr
 */

declare(strict_types=1);

trait ZSU_sunset
{
    public function ExecuteSunsetAction(): void
    {
        if ($this->CheckMaintenanceMode()) {
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
            $toggleAction = boolval($this->ReadPropertyInteger('SunsetToggleAction'));
            $this->ToggleState($toggleAction);
        }
    }

    #################### Private

    private function CheckSunset(): void
    {
        if ($this->CheckMaintenanceMode()) {
            return;
        }
        if (!$this->CheckAutomaticMode()) {
            return;
        }
        if (!$this->ReadPropertyBoolean('UseSunset')) {
            $this->SendDebug(__FUNCTION__, 'Es wird kein Sonnentuntergang verwendet!', 0);
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
                        $this->SetNextToggleTime();
                    }
                }
            }
        }
    }
}
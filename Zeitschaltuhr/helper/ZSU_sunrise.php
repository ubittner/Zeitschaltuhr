<?php

/** @noinspection PhpUnused */

/*
 * @author      Ulrich Bittner
 * @copyright   (c) 2021
 * @license     CC BY-NC-SA 4.0
 * @see         https://github.com/ubittner/Zeitschaltuhr/tree/main/Zeitschaltuhr
 */

declare(strict_types=1);

trait ZSU_sunrise
{
    public function ExecuteSunriseAction(): void
    {
        if ($this->CheckMaintenanceMode()) {
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
            $toggleAction = boolval($this->ReadPropertyInteger('SunriseToggleAction'));
            $this->ToggleState($toggleAction);
        }
    }

    #################### Private

    private function CheckSunrise(): void
    {
        if ($this->CheckMaintenanceMode()) {
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
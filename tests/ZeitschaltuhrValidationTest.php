<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class ZeitschaltuhrValidationTest extends TestCaseSymconValidation
{
    public function testValidateLibrary(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }

    public function testValidateModule_Zeitschaltuhr(): void
    {
        $this->validateModule(__DIR__ . '/../Zeitschaltuhr');
    }
}
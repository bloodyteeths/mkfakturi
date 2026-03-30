<?php

namespace Modules\Mk\Payroll\Services;

use App\Models\PayrollEmployee;
use Illuminate\Support\Facades\Log;

/**
 * ФПИОМ Form Service
 *
 * Generates M1 (registration) and M2 (deregistration) XML forms
 * for the Fund for Pension and Disability Insurance of Macedonia.
 *
 * M1 = Пријава за осигурување (registration of employment)
 * M2 = Одјава од осигурување (deregistration of employment)
 */
class FpiomFormService
{
    /**
     * Generate M1 XML (employee registration form)
     */
    public function generateM1Xml(PayrollEmployee $employee): string
    {
        $company = $employee->company;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><M1 xmlns="urn:fpiom.gov.mk:m1:v1"></M1>');

        // Header
        $header = $xml->addChild('Header');
        $header->addChild('DocumentType', 'M1');
        $header->addChild('Version', '1.0');
        $header->addChild('GeneratedAt', now()->format('Y-m-d\TH:i:s'));

        // Company (Employer)
        $employer = $xml->addChild('Employer');
        $employer->addChild('CompanyName', htmlspecialchars($company->name ?? ''));
        $employer->addChild('EDB', $company->edb ?? '');
        $employer->addChild('EMBG_Owner', '');
        $employer->addChild('Address', htmlspecialchars($company->address_street_1 ?? ''));
        $employer->addChild('City', htmlspecialchars($company->city ?? ''));

        // Employee
        $emp = $xml->addChild('Employee');
        $emp->addChild('EMBG', $employee->embg ?? '');
        $emp->addChild('FirstName', htmlspecialchars($employee->first_name ?? ''));
        $emp->addChild('LastName', htmlspecialchars($employee->last_name ?? ''));
        $emp->addChild('DateOfBirth', $employee->date_of_birth ? $employee->date_of_birth->format('Y-m-d') : '');
        $emp->addChild('Gender', $employee->gender ?? '');
        $emp->addChild('Nationality', $employee->nationality ?? 'MK');
        $emp->addChild('Address', htmlspecialchars($employee->address ?? ''));
        $emp->addChild('City', htmlspecialchars($employee->city ?? ''));

        // Employment details
        $employment = $xml->addChild('Employment');
        $employment->addChild('StartDate', $employee->hire_date ? $employee->hire_date->format('Y-m-d') : '');
        $employment->addChild('EmploymentType', $employee->employment_type ?? 'full_time');
        $employment->addChild('Position', htmlspecialchars($employee->position ?? ''));
        $employment->addChild('OccupationCode', $employee->occupation_code ?? '');
        $employment->addChild('WorkHoursPerWeek', $employee->work_hours_per_week ?? '40');
        $employment->addChild('ContractType', $employee->contract_type ?? 'indefinite');

        // Insurance basis
        $insurance = $xml->addChild('Insurance');
        $insurance->addChild('PensionInsurance', 'true');
        $insurance->addChild('HealthInsurance', 'true');
        $insurance->addChild('UnemploymentInsurance', 'true');

        Log::info('Generated M1 form', ['employee_id' => $employee->id]);

        return $xml->asXML();
    }

    /**
     * Generate M2 XML (employee deregistration form)
     */
    public function generateM2Xml(PayrollEmployee $employee): string
    {
        $company = $employee->company;

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><M2 xmlns="urn:fpiom.gov.mk:m2:v1"></M2>');

        // Header
        $header = $xml->addChild('Header');
        $header->addChild('DocumentType', 'M2');
        $header->addChild('Version', '1.0');
        $header->addChild('GeneratedAt', now()->format('Y-m-d\TH:i:s'));

        // Company (Employer)
        $employer = $xml->addChild('Employer');
        $employer->addChild('CompanyName', htmlspecialchars($company->name ?? ''));
        $employer->addChild('EDB', $company->edb ?? '');

        // Employee
        $emp = $xml->addChild('Employee');
        $emp->addChild('EMBG', $employee->embg ?? '');
        $emp->addChild('FirstName', htmlspecialchars($employee->first_name ?? ''));
        $emp->addChild('LastName', htmlspecialchars($employee->last_name ?? ''));

        // Deregistration details
        $dereg = $xml->addChild('Deregistration');
        $dereg->addChild('EndDate', $employee->termination_date ? $employee->termination_date->format('Y-m-d') : now()->format('Y-m-d'));
        $dereg->addChild('Reason', $employee->termination_reason ?? 'resignation');
        $dereg->addChild('LastWorkingDay', $employee->termination_date ? $employee->termination_date->format('Y-m-d') : now()->format('Y-m-d'));

        Log::info('Generated M2 form', ['employee_id' => $employee->id]);

        return $xml->asXML();
    }
}

// CLAUDE-CHECKPOINT

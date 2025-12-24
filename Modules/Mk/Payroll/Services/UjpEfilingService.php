<?php

namespace Modules\Mk\Payroll\Services;

use App\Models\Company;
use App\Models\PayrollRun;
use App\Models\PayrollRunLine;
use DOMDocument;
use DOMException;
use Illuminate\Support\Collection;

/**
 * UJP E-Filing Service
 *
 * Generates XML files for electronic filing with the Macedonian Public Revenue Office (UJP):
 * - MPIN: Monthly Payroll Income Notification (monthly tax filing)
 * - DDV-04: Annual Employee Income Report (yearly employee income summary)
 *
 * Based on UJP e-filing requirements for payroll tax submissions.
 */
class UjpEfilingService
{
    /**
     * Generate MPIN XML for monthly payroll tax filing
     *
     * MPIN (Monthly Payroll Income Notification) contains:
     * - Company information (tax ID, name, address)
     * - Tax period (year, month)
     * - Employee-level details (EMBG, gross, contributions, income tax)
     * - Summary totals for all employees
     *
     * @param PayrollRun $payrollRun
     * @param Company $company
     * @return string XML content
     * @throws DOMException
     */
    public function generateMpinXml(PayrollRun $payrollRun, Company $company): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Root element
        $root = $dom->createElement('MPIN');
        $root->setAttribute('xmlns', 'http://www.ujp.gov.mk/mpin');
        $root->setAttribute('version', '1.0');
        $dom->appendChild($root);

        // Header section
        $header = $this->createMpinHeader($dom, $payrollRun);
        $root->appendChild($header);

        // Employer section
        $employer = $this->createEmployerSection($dom, $company);
        $root->appendChild($employer);

        // Tax period section
        $taxPeriod = $this->createTaxPeriodSection($dom, $payrollRun);
        $root->appendChild($taxPeriod);

        // Employees section
        $employees = $this->createEmployeesSection($dom, $payrollRun);
        $root->appendChild($employees);

        // Summary section
        $summary = $this->createMpinSummary($dom, $payrollRun);
        $root->appendChild($summary);

        // Declaration section
        $declaration = $this->createDeclarationSection($dom, $company);
        $root->appendChild($declaration);

        return $dom->saveXML();
    }

    /**
     * Generate DDV-04 XML for annual employee income report
     *
     * DDV-04 (Annual Employee Income Report) contains:
     * - Company information
     * - Tax year
     * - Per-employee annual totals (gross income, contributions, tax)
     * - Summary of all employees for the year
     *
     * @param int $year Tax year
     * @param Company $company
     * @param Collection $payrollRuns Collection of PayrollRun for the year
     * @return string XML content
     * @throws DOMException
     */
    public function generateDdv04Xml(int $year, Company $company, Collection $payrollRuns): string
    {
        $dom = new DOMDocument('1.0', 'UTF-8');
        $dom->formatOutput = true;

        // Root element
        $root = $dom->createElement('DDV04');
        $root->setAttribute('xmlns', 'http://www.ujp.gov.mk/ddv04');
        $root->setAttribute('version', '1.0');
        $dom->appendChild($root);

        // Header section
        $header = $this->createDdv04Header($dom, $year);
        $root->appendChild($header);

        // Employer section
        $employer = $this->createEmployerSection($dom, $company);
        $root->appendChild($employer);

        // Tax year section
        $taxYear = $this->createTaxYearSection($dom, $year);
        $root->appendChild($taxYear);

        // Aggregate employee data by employee_id
        $employeeAnnualData = $this->aggregateEmployeeAnnualData($payrollRuns);

        // Employees section
        $employees = $this->createDdv04EmployeesSection($dom, $employeeAnnualData);
        $root->appendChild($employees);

        // Summary section
        $summary = $this->createDdv04Summary($dom, $employeeAnnualData);
        $root->appendChild($summary);

        // Declaration section
        $declaration = $this->createDeclarationSection($dom, $company);
        $root->appendChild($declaration);

        return $dom->saveXML();
    }

    /**
     * Validate MPIN XML against schema (if schema is available)
     *
     * @param string $xml
     * @return bool
     */
    public function validateMpinXml(string $xml): bool
    {
        $schemaPath = storage_path('schemas/mk_mpin.xsd');

        if (! file_exists($schemaPath)) {
            // Schema not available - skip validation
            return true;
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        return $dom->schemaValidate($schemaPath);
    }

    /**
     * Validate DDV-04 XML against schema (if schema is available)
     *
     * @param string $xml
     * @return bool
     */
    public function validateDdv04Xml(string $xml): bool
    {
        $schemaPath = storage_path('schemas/mk_ddv04_payroll.xsd');

        if (! file_exists($schemaPath)) {
            // Schema not available - skip validation
            return true;
        }

        $dom = new DOMDocument();
        $dom->loadXML($xml);

        return $dom->schemaValidate($schemaPath);
    }

    /**
     * Create MPIN header section
     */
    private function createMpinHeader(DOMDocument $dom, PayrollRun $payrollRun): \DOMElement
    {
        $header = $dom->createElement('Header');

        $this->appendElement($dom, $header, 'DocumentType', 'MPIN');
        $this->appendElement($dom, $header, 'SubmissionDate', now()->format('Y-m-d'));
        $this->appendElement($dom, $header, 'FormVersion', '2024');

        // Software info
        $software = $dom->createElement('SoftwareInfo');
        $this->appendElement($dom, $software, 'SoftwareName', 'Facturino');
        $this->appendElement($dom, $software, 'SoftwareVersion', '1.0');
        $this->appendElement($dom, $software, 'SoftwareProvider', 'Facturino.mk');
        $header->appendChild($software);

        return $header;
    }

    /**
     * Create DDV-04 header section
     */
    private function createDdv04Header(DOMDocument $dom, int $year): \DOMElement
    {
        $header = $dom->createElement('Header');

        $this->appendElement($dom, $header, 'DocumentType', 'DDV-04');
        $this->appendElement($dom, $header, 'SubmissionDate', now()->format('Y-m-d'));
        $this->appendElement($dom, $header, 'TaxYear', (string) $year);
        $this->appendElement($dom, $header, 'FormVersion', '2024');

        // Software info
        $software = $dom->createElement('SoftwareInfo');
        $this->appendElement($dom, $software, 'SoftwareName', 'Facturino');
        $this->appendElement($dom, $software, 'SoftwareVersion', '1.0');
        $this->appendElement($dom, $software, 'SoftwareProvider', 'Facturino.mk');
        $header->appendChild($software);

        return $header;
    }

    /**
     * Create employer section
     */
    private function createEmployerSection(DOMDocument $dom, Company $company): \DOMElement
    {
        $employer = $dom->createElement('Employer');

        $this->appendElement($dom, $employer, 'TaxID', $company->unique_hash ?? '');
        $this->appendElement($dom, $employer, 'CompanyName', $company->name);

        // Address
        $address = $dom->createElement('Address');
        $this->appendElement($dom, $address, 'Street', $company->address->address_street_1 ?? '');
        $this->appendElement($dom, $address, 'City', $company->address->city ?? '');
        $this->appendElement($dom, $address, 'PostalCode', $company->address->zip ?? '');
        $this->appendElement($dom, $address, 'Country', $company->address->country->code ?? 'MK');
        $employer->appendChild($address);

        // Contact info
        $contact = $dom->createElement('ContactInfo');
        $this->appendElement($dom, $contact, 'Phone', $company->address->phone ?? '');
        $this->appendElement($dom, $contact, 'Email', $company->owner->email ?? '');
        $employer->appendChild($contact);

        return $employer;
    }

    /**
     * Create tax period section
     */
    private function createTaxPeriodSection(DOMDocument $dom, PayrollRun $payrollRun): \DOMElement
    {
        $taxPeriod = $dom->createElement('TaxPeriod');

        $this->appendElement($dom, $taxPeriod, 'Year', (string) $payrollRun->period_year);
        $this->appendElement($dom, $taxPeriod, 'Month', str_pad((string) $payrollRun->period_month, 2, '0', STR_PAD_LEFT));
        $this->appendElement($dom, $taxPeriod, 'PeriodStart', $payrollRun->period_start->format('Y-m-d'));
        $this->appendElement($dom, $taxPeriod, 'PeriodEnd', $payrollRun->period_end->format('Y-m-d'));

        return $taxPeriod;
    }

    /**
     * Create tax year section
     */
    private function createTaxYearSection(DOMDocument $dom, int $year): \DOMElement
    {
        $taxYear = $dom->createElement('TaxYear');

        $this->appendElement($dom, $taxYear, 'Year', (string) $year);
        $this->appendElement($dom, $taxYear, 'PeriodStart', "$year-01-01");
        $this->appendElement($dom, $taxYear, 'PeriodEnd', "$year-12-31");

        return $taxYear;
    }

    /**
     * Create employees section for MPIN
     */
    private function createEmployeesSection(DOMDocument $dom, PayrollRun $payrollRun): \DOMElement
    {
        $employees = $dom->createElement('Employees');

        // Load payroll lines with employee relationships
        $lines = $payrollRun->lines()->with('employee')->where('status', 'included')->get();

        foreach ($lines as $line) {
            $employeeNode = $this->createEmployeeNode($dom, $line);
            $employees->appendChild($employeeNode);
        }

        return $employees;
    }

    /**
     * Create employee node for MPIN
     */
    private function createEmployeeNode(DOMDocument $dom, PayrollRunLine $line): \DOMElement
    {
        $employee = $dom->createElement('Employee');

        $this->appendElement($dom, $employee, 'EMBG', $line->employee->embg);
        $this->appendElement($dom, $employee, 'FirstName', $line->employee->first_name);
        $this->appendElement($dom, $employee, 'LastName', $line->employee->last_name);
        $this->appendElement($dom, $employee, 'EmployeeNumber', $line->employee->employee_number);

        // Income details
        $income = $dom->createElement('Income');
        $this->appendMonetaryElement($dom, $income, 'GrossSalary', $line->gross_salary);
        $this->appendMonetaryElement($dom, $income, 'TransportAllowance', $line->transport_allowance ?? 0);
        $this->appendMonetaryElement($dom, $income, 'MealAllowance', $line->meal_allowance ?? 0);
        $this->appendElement($dom, $income, 'WorkingDays', (string) $line->working_days);
        $this->appendElement($dom, $income, 'WorkedDays', (string) $line->worked_days);
        $employee->appendChild($income);

        // Contributions
        $contributions = $dom->createElement('Contributions');
        $this->appendMonetaryElement($dom, $contributions, 'PensionEmployee', $line->pension_contribution_employee);
        $this->appendMonetaryElement($dom, $contributions, 'PensionEmployer', $line->pension_contribution_employer);
        $this->appendMonetaryElement($dom, $contributions, 'HealthEmployee', $line->health_contribution_employee);
        $this->appendMonetaryElement($dom, $contributions, 'HealthEmployer', $line->health_contribution_employer);
        $this->appendMonetaryElement($dom, $contributions, 'Unemployment', $line->unemployment_contribution);
        $this->appendMonetaryElement($dom, $contributions, 'Additional', $line->additional_contribution);
        $employee->appendChild($contributions);

        // Tax
        $tax = $dom->createElement('Tax');
        $taxableBase = $line->gross_salary
            - $line->pension_contribution_employee
            - $line->health_contribution_employee
            - $line->unemployment_contribution
            - $line->additional_contribution;
        $this->appendMonetaryElement($dom, $tax, 'TaxableBase', $taxableBase);
        $this->appendMonetaryElement($dom, $tax, 'IncomeTax', $line->income_tax_amount);
        $employee->appendChild($tax);

        // Net salary
        $this->appendMonetaryElement($dom, $employee, 'NetSalary', $line->net_salary);

        return $employee;
    }

    /**
     * Create MPIN summary section
     */
    private function createMpinSummary(DOMDocument $dom, PayrollRun $payrollRun): \DOMElement
    {
        $summary = $dom->createElement('Summary');

        $this->appendElement($dom, $summary, 'EmployeeCount', (string) $payrollRun->lines()->where('status', 'included')->count());
        $this->appendMonetaryElement($dom, $summary, 'TotalGrossSalary', $payrollRun->total_gross);
        $this->appendMonetaryElement($dom, $summary, 'TotalNetSalary', $payrollRun->total_net);
        $this->appendMonetaryElement($dom, $summary, 'TotalEmployeeTax', $payrollRun->total_employee_tax);
        $this->appendMonetaryElement($dom, $summary, 'TotalEmployerTax', $payrollRun->total_employer_tax);
        $this->appendMonetaryElement($dom, $summary, 'TotalEmployerCost', $payrollRun->total_gross + $payrollRun->total_employer_tax);

        return $summary;
    }

    /**
     * Aggregate employee annual data from multiple payroll runs
     */
    private function aggregateEmployeeAnnualData(Collection $payrollRuns): Collection
    {
        $employeeData = collect();

        foreach ($payrollRuns as $run) {
            foreach ($run->lines()->with('employee')->where('status', 'included')->get() as $line) {
                $employeeId = $line->employee_id;

                if (! $employeeData->has($employeeId)) {
                    $employeeData[$employeeId] = [
                        'employee' => $line->employee,
                        'total_gross' => 0,
                        'total_net' => 0,
                        'total_pension_employee' => 0,
                        'total_pension_employer' => 0,
                        'total_health_employee' => 0,
                        'total_health_employer' => 0,
                        'total_unemployment' => 0,
                        'total_additional' => 0,
                        'total_income_tax' => 0,
                        'months_worked' => 0,
                    ];
                }

                $employeeData[$employeeId]['total_gross'] += $line->gross_salary;
                $employeeData[$employeeId]['total_net'] += $line->net_salary;
                $employeeData[$employeeId]['total_pension_employee'] += $line->pension_contribution_employee;
                $employeeData[$employeeId]['total_pension_employer'] += $line->pension_contribution_employer;
                $employeeData[$employeeId]['total_health_employee'] += $line->health_contribution_employee;
                $employeeData[$employeeId]['total_health_employer'] += $line->health_contribution_employer;
                $employeeData[$employeeId]['total_unemployment'] += $line->unemployment_contribution;
                $employeeData[$employeeId]['total_additional'] += $line->additional_contribution;
                $employeeData[$employeeId]['total_income_tax'] += $line->income_tax_amount;
                $employeeData[$employeeId]['months_worked']++;
            }
        }

        return $employeeData;
    }

    /**
     * Create employees section for DDV-04
     */
    private function createDdv04EmployeesSection(DOMDocument $dom, Collection $employeeData): \DOMElement
    {
        $employees = $dom->createElement('Employees');

        foreach ($employeeData as $data) {
            $employeeNode = $this->createDdv04EmployeeNode($dom, $data);
            $employees->appendChild($employeeNode);
        }

        return $employees;
    }

    /**
     * Create employee node for DDV-04
     */
    private function createDdv04EmployeeNode(DOMDocument $dom, array $data): \DOMElement
    {
        $employee = $dom->createElement('Employee');

        $this->appendElement($dom, $employee, 'EMBG', $data['employee']->embg);
        $this->appendElement($dom, $employee, 'FirstName', $data['employee']->first_name);
        $this->appendElement($dom, $employee, 'LastName', $data['employee']->last_name);
        $this->appendElement($dom, $employee, 'EmployeeNumber', $data['employee']->employee_number);

        // Annual totals
        $annualIncome = $dom->createElement('AnnualIncome');
        $this->appendMonetaryElement($dom, $annualIncome, 'TotalGross', $data['total_gross']);
        $this->appendMonetaryElement($dom, $annualIncome, 'TotalNet', $data['total_net']);
        $this->appendElement($dom, $annualIncome, 'MonthsWorked', (string) $data['months_worked']);
        $employee->appendChild($annualIncome);

        // Annual contributions
        $annualContributions = $dom->createElement('AnnualContributions');
        $this->appendMonetaryElement($dom, $annualContributions, 'PensionEmployee', $data['total_pension_employee']);
        $this->appendMonetaryElement($dom, $annualContributions, 'PensionEmployer', $data['total_pension_employer']);
        $this->appendMonetaryElement($dom, $annualContributions, 'HealthEmployee', $data['total_health_employee']);
        $this->appendMonetaryElement($dom, $annualContributions, 'HealthEmployer', $data['total_health_employer']);
        $this->appendMonetaryElement($dom, $annualContributions, 'Unemployment', $data['total_unemployment']);
        $this->appendMonetaryElement($dom, $annualContributions, 'Additional', $data['total_additional']);
        $employee->appendChild($annualContributions);

        // Annual tax
        $annualTax = $dom->createElement('AnnualTax');
        $this->appendMonetaryElement($dom, $annualTax, 'TotalIncomeTax', $data['total_income_tax']);
        $employee->appendChild($annualTax);

        return $employee;
    }

    /**
     * Create DDV-04 summary section
     */
    private function createDdv04Summary(DOMDocument $dom, Collection $employeeData): \DOMElement
    {
        $summary = $dom->createElement('Summary');

        $totalGross = $employeeData->sum('total_gross');
        $totalNet = $employeeData->sum('total_net');
        $totalPensionEmployee = $employeeData->sum('total_pension_employee');
        $totalPensionEmployer = $employeeData->sum('total_pension_employer');
        $totalHealthEmployee = $employeeData->sum('total_health_employee');
        $totalHealthEmployer = $employeeData->sum('total_health_employer');
        $totalUnemployment = $employeeData->sum('total_unemployment');
        $totalAdditional = $employeeData->sum('total_additional');
        $totalIncomeTax = $employeeData->sum('total_income_tax');

        $this->appendElement($dom, $summary, 'EmployeeCount', (string) $employeeData->count());
        $this->appendMonetaryElement($dom, $summary, 'TotalGrossSalary', $totalGross);
        $this->appendMonetaryElement($dom, $summary, 'TotalNetSalary', $totalNet);
        $this->appendMonetaryElement($dom, $summary, 'TotalPensionEmployee', $totalPensionEmployee);
        $this->appendMonetaryElement($dom, $summary, 'TotalPensionEmployer', $totalPensionEmployer);
        $this->appendMonetaryElement($dom, $summary, 'TotalHealthEmployee', $totalHealthEmployee);
        $this->appendMonetaryElement($dom, $summary, 'TotalHealthEmployer', $totalHealthEmployer);
        $this->appendMonetaryElement($dom, $summary, 'TotalUnemployment', $totalUnemployment);
        $this->appendMonetaryElement($dom, $summary, 'TotalAdditional', $totalAdditional);
        $this->appendMonetaryElement($dom, $summary, 'TotalIncomeTax', $totalIncomeTax);

        $totalEmployeeTax = $totalPensionEmployee + $totalHealthEmployee + $totalUnemployment + $totalAdditional + $totalIncomeTax;
        $totalEmployerTax = $totalPensionEmployer + $totalHealthEmployer;

        $this->appendMonetaryElement($dom, $summary, 'TotalEmployeeTax', $totalEmployeeTax);
        $this->appendMonetaryElement($dom, $summary, 'TotalEmployerTax', $totalEmployerTax);
        $this->appendMonetaryElement($dom, $summary, 'TotalEmployerCost', $totalGross + $totalEmployerTax);

        return $summary;
    }

    /**
     * Create declaration section
     */
    private function createDeclarationSection(DOMDocument $dom, Company $company): \DOMElement
    {
        $declaration = $dom->createElement('Declaration');

        $owner = $company->owner;

        $this->appendElement($dom, $declaration, 'DeclarantName', $owner->name ?? '');
        $this->appendElement($dom, $declaration, 'DeclarantPosition', 'Owner');
        $this->appendElement($dom, $declaration, 'DeclarationDate', now()->format('Y-m-d'));

        // Responsible person
        $responsiblePerson = $dom->createElement('ResponsiblePerson');
        $this->appendElement($dom, $responsiblePerson, 'Name', $owner->name ?? '');
        $this->appendElement($dom, $responsiblePerson, 'Position', 'Owner');
        $this->appendElement($dom, $responsiblePerson, 'Email', $owner->email ?? '');
        $declaration->appendChild($responsiblePerson);

        return $declaration;
    }

    /**
     * Helper: Append a simple text element
     */
    private function appendElement(DOMDocument $dom, \DOMElement $parent, string $name, string $value): void
    {
        $element = $dom->createElement($name, htmlspecialchars($value, ENT_XML1, 'UTF-8'));
        $parent->appendChild($element);
    }

    /**
     * Helper: Append a monetary amount element (converts from cents to decimal)
     */
    private function appendMonetaryElement(DOMDocument $dom, \DOMElement $parent, string $name, int $cents): void
    {
        $value = number_format($cents / 100, 2, '.', '');
        $element = $dom->createElement($name, $value);
        $parent->appendChild($element);
    }
}

// LLM-CHECKPOINT

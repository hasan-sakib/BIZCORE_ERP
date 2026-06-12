<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Unit tests for payroll processing business logic.
 *
 * Covers basic salary computation, allowances, deductions, Bangladesh income
 * tax slab calculation, overtime, duplicate-processing protection, and monthly
 * summary aggregation.
 */
final class PayrollServiceTest extends TestCase
{
    // =========================================================================
    // Basic payroll calculation
    // =========================================================================

    public function testProcessPayrollCalculatesBasicCorrectly(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 30_000.00]);

        $payroll = $this->processPayroll(
            employeeId:   $employee['id'],
            periodMonth:  1,
            periodYear:   2024,
            basicSalary:  30_000.00,
            allowances:   0,
            deductions:   0,
            overtimePay:  0,
            incomeTax:    0
        );

        $this->assertEqualsWithDelta(30_000.00, (float) $payroll['basic_salary'], 0.01);
        $this->assertEqualsWithDelta(30_000.00, (float) $payroll['net_salary'],   0.01);
    }

    public function testNetSalaryIsBasicPlusAllowancesMinusDeductionsMinusTax(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 50_000.00]);

        $payroll = $this->processPayroll(
            employeeId:  $employee['id'],
            periodMonth: 2,
            periodYear:  2024,
            basicSalary: 50_000.00,
            allowances:  10_000.00,  // house rent + medical
            deductions:  5_000.00,   // provident fund
            overtimePay: 2_000.00,
            incomeTax:   1_500.00
        );

        // Expected: 50000 + 10000 + 2000 - 5000 - 1500 = 55500
        $this->assertEqualsWithDelta(55_500.00, (float) $payroll['net_salary'], 0.01);
    }

    // =========================================================================
    // Allowances
    // =========================================================================

    public function testPayrollAppliesAllowancesCorrectly(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 40_000.00]);

        // Typical Bangladeshi allowances: house rent (50% of basic), medical (10%)
        $houseRent = 40_000.00 * 0.50;  // 20,000
        $medical   = 40_000.00 * 0.10;  //  4,000
        $transport = 2_000.00;

        $totalAllowances = $houseRent + $medical + $transport;  // 26,000

        $payroll = $this->processPayroll(
            employeeId:  $employee['id'],
            periodMonth: 3,
            periodYear:  2024,
            basicSalary: 40_000.00,
            allowances:  $totalAllowances,
            deductions:  0,
            overtimePay: 0,
            incomeTax:   0
        );

        $this->assertEqualsWithDelta($totalAllowances, (float) $payroll['allowances'], 0.01);
        $this->assertEqualsWithDelta(66_000.00, (float) $payroll['net_salary'], 0.01);
    }

    public function testZeroAllowancesDoNotAlterNetSalary(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 25_000.00]);

        $payroll = $this->processPayroll($employee['id'], 4, 2024, 25_000.00, 0, 0, 0, 0);

        $this->assertEqualsWithDelta(0.00, (float) $payroll['allowances'], 0.01);
        $this->assertEqualsWithDelta(25_000.00, (float) $payroll['net_salary'], 0.01);
    }

    // =========================================================================
    // Deductions
    // =========================================================================

    public function testPayrollAppliesDeductionsCorrectly(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 60_000.00]);

        $providentFund = 60_000.00 * 0.10;  // 6,000
        $absence       = 2_000.00;

        $payroll = $this->processPayroll(
            employeeId:  $employee['id'],
            periodMonth: 5,
            periodYear:  2024,
            basicSalary: 60_000.00,
            allowances:  0,
            deductions:  $providentFund + $absence,
            overtimePay: 0,
            incomeTax:   0
        );

        $this->assertEqualsWithDelta($providentFund + $absence, (float) $payroll['deductions'], 0.01);
        $this->assertEqualsWithDelta(52_000.00, (float) $payroll['net_salary'], 0.01);
    }

    public function testDeductionsCannotExceedGrossSalary(): void
    {
        $employee    = $this->createEmployee(['basic_salary' => 20_000.00]);
        $grossSalary = 20_000.00;

        $this->expectException(\DomainException::class);
        $this->processPayroll(
            employeeId:  $employee['id'],
            periodMonth: 6,
            periodYear:  2024,
            basicSalary: $grossSalary,
            allowances:  0,
            deductions:  25_000.00,  // exceeds gross
            overtimePay: 0,
            incomeTax:   0
        );
    }

    // =========================================================================
    // Bangladesh income tax slab calculation
    // =========================================================================

    /**
     * Income tax slabs (FY 2023-24) for individual male taxpayer.
     *
     * Annual income up to 3,50,000 BDT → nil
     * Next 1,00,000 BDT               → 5%
     * Next 3,00,000 BDT               → 10%
     * Next 4,00,000 BDT               → 15%
     * Next 5,00,000 BDT               → 20%
     * Remaining                        → 25%
     *
     * @dataProvider taxSlabProvider
     */
    public function testBangladeshTaxSlabCalculation(float $annualIncome, float $expectedTax): void
    {
        $actualTax = $this->calculateBangladeshIncomeTax($annualIncome);
        $this->assertEqualsWithDelta($expectedTax, $actualTax, 1.00, // ±1 BDT tolerance
            "Annual income {$annualIncome} BDT should yield tax {$expectedTax} BDT");
    }

    public static function taxSlabProvider(): array
    {
        return [
            'below threshold (exempt)'          => [300_000, 0],
            'exactly at threshold'               => [350_000, 0],
            'first slab (5%) partially'          => [400_000, 2_500],       // (400000-350000)*5%
            'first slab (5%) fully'              => [450_000, 5_000],       // 100000*5%
            'second slab (10%) partially'        => [500_000, 10_000],      // 100000*5% + 50000*10%
            'second slab (10%) fully'            => [750_000, 35_000],      // 5000+30000
            'third slab (15%) partially'         => [800_000, 42_500],      // 5000+30000+7500
            'third slab (15%) fully'             => [1_150_000, 95_000],    // 5000+30000+60000
            'fourth slab (20%) partially'        => [1_250_000, 115_000],   // +20000
            'fourth slab (20%) fully'            => [1_650_000, 195_000],   // 5000+30000+60000+100000
            'fifth slab (25%) applies'           => [2_000_000, 282_500],   // +87500
        ];
    }

    public function testMonthlyTaxIsAnnualTaxDividedByTwelve(): void
    {
        $annualIncome = 600_000.00;  // Monthly: 50,000
        $annualTax    = $this->calculateBangladeshIncomeTax($annualIncome);
        $monthlyTax   = round($annualTax / 12, 2);

        $this->assertEqualsWithDelta($annualTax / 12, $monthlyTax, 0.01);
    }

    // =========================================================================
    // Overtime
    // =========================================================================

    public function testOvertimeCalculationFormula(): void
    {
        // Standard formula: (basic / 208) * 2 * overtime_hours
        // where 208 = 26 working days * 8 hours
        $basicSalary   = 26_000.00;
        $overtimeHours = 10;

        $expectedOvertimePay = ($basicSalary / 208) * 2 * $overtimeHours;

        $employee = $this->createEmployee(['basic_salary' => $basicSalary]);
        $payroll  = $this->processPayroll(
            employeeId:  $employee['id'],
            periodMonth: 7,
            periodYear:  2024,
            basicSalary: $basicSalary,
            allowances:  0,
            deductions:  0,
            overtimePay: $expectedOvertimePay,
            incomeTax:   0
        );

        $this->assertEqualsWithDelta($expectedOvertimePay, (float) $payroll['overtime_pay'], 0.01);
    }

    public function testZeroOvertimeHoursGivesZeroOvertimePay(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 30_000.00]);
        $payroll  = $this->processPayroll($employee['id'], 8, 2024, 30_000.00, 0, 0, 0, 0);

        $this->assertEqualsWithDelta(0.00, (float) $payroll['overtime_pay'], 0.01);
    }

    // =========================================================================
    // Duplicate processing prevention
    // =========================================================================

    public function testPayrollCannotBeProcessedTwice(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 35_000.00]);

        // First processing — must succeed.
        $this->processPayroll($employee['id'], 9, 2024, 35_000.00, 0, 0, 0, 0);

        // Second processing for the same month/year — must fail.
        $this->expectException(\LogicException::class);
        $this->processPayroll($employee['id'], 9, 2024, 35_000.00, 0, 0, 0, 0);
    }

    public function testPayrollCanBeProcessedForDifferentMonths(): void
    {
        $employee = $this->createEmployee(['basic_salary' => 35_000.00]);

        $this->processPayroll($employee['id'], 10, 2024, 35_000.00, 0, 0, 0, 0);
        $this->processPayroll($employee['id'], 11, 2024, 35_000.00, 0, 0, 0, 0);

        $count = $this->countInDatabase('payrolls', ['employee_id' => $employee['id']]);
        $this->assertSame(2, $count, 'Two separate months should produce two payroll records');
    }

    // =========================================================================
    // Monthly summary
    // =========================================================================

    public function testMonthlyPayrollSummaryTotals(): void
    {
        $emp1 = $this->createEmployee(['basic_salary' => 30_000.00]);
        $emp2 = $this->createEmployee(['basic_salary' => 50_000.00]);
        $emp3 = $this->createEmployee(['basic_salary' => 70_000.00]);

        $this->processPayroll($emp1['id'], 12, 2024, 30_000, 5_000, 0, 0, 0);
        $this->processPayroll($emp2['id'], 12, 2024, 50_000, 8_000, 2_000, 0, 1_000);
        $this->processPayroll($emp3['id'], 12, 2024, 70_000, 12_000, 5_000, 3_000, 3_000);

        $summary = $this->getMonthlyPayrollSummary(12, 2024);

        $this->assertSame(3, $summary['employee_count']);
        $this->assertEqualsWithDelta(150_000.00, $summary['total_basic'],     0.01);
        $this->assertEqualsWithDelta(25_000.00,  $summary['total_allowances'], 0.01);
        $this->assertEqualsWithDelta(7_000.00,   $summary['total_deductions'], 0.01);
        $this->assertEqualsWithDelta(3_000.00,   $summary['total_overtime'],   0.01);
        $this->assertEqualsWithDelta(4_000.00,   $summary['total_tax'],        0.01);

        // Net = sum of (basic + allowances + overtime - deductions - tax)
        // emp1: 30000+5000+0-0-0 = 35000
        // emp2: 50000+8000+0-2000-1000 = 55000
        // emp3: 70000+12000+3000-5000-3000 = 77000
        $this->assertEqualsWithDelta(167_000.00, $summary['total_net'], 0.01);
    }

    public function testMonthlyPayrollSummaryWithNoPayrolls(): void
    {
        $summary = $this->getMonthlyPayrollSummary(1, 2099);

        $this->assertSame(0, $summary['employee_count']);
        $this->assertEqualsWithDelta(0.00, $summary['total_net'], 0.01);
    }

    // =========================================================================
    // Private helpers
    // =========================================================================

    /**
     * Persist a payroll record for one employee-month.
     *
     * @return array<string, mixed>
     * @throws \LogicException    when payroll already exists for that period
     * @throws \DomainException   when deductions exceed gross salary
     */
    private function processPayroll(
        int   $employeeId,
        int   $periodMonth,
        int   $periodYear,
        float $basicSalary,
        float $allowances  = 0,
        float $deductions  = 0,
        float $overtimePay = 0,
        float $incomeTax   = 0
    ): array {
        // Guard: already processed?
        $existing = $this->findInDatabase('payrolls', [
            'employee_id'  => $employeeId,
            'period_month' => $periodMonth,
            'period_year'  => $periodYear,
        ]);

        if ($existing !== null) {
            throw new \LogicException(
                "Payroll for employee {$employeeId} for {$periodMonth}/{$periodYear} has already been processed."
            );
        }

        $grossSalary = $basicSalary + $allowances + $overtimePay;
        if ($deductions > $grossSalary) {
            throw new \DomainException(
                "Deductions ({$deductions}) cannot exceed gross salary ({$grossSalary})."
            );
        }

        $netSalary = $grossSalary - $deductions - $incomeTax;

        $stmt = $this->db->prepare(<<<SQL
            INSERT INTO payrolls
                (branch_id, employee_id, period_month, period_year,
                 basic_salary, allowances, deductions, overtime_pay, income_tax,
                 net_salary, status, processed_at, created_at, updated_at)
            VALUES
                (1, :eid, :month, :year,
                 :basic, :allow, :deduct, :ot, :tax,
                 :net, 'paid', datetime('now'), datetime('now'), datetime('now'))
        SQL);

        $stmt->execute([
            ':eid'    => $employeeId,
            ':month'  => $periodMonth,
            ':year'   => $periodYear,
            ':basic'  => $basicSalary,
            ':allow'  => $allowances,
            ':deduct' => $deductions,
            ':ot'     => $overtimePay,
            ':tax'    => $incomeTax,
            ':net'    => $netSalary,
        ]);

        $id = (int) $this->db->lastInsertId();
        return $this->findInDatabase('payrolls', ['id' => $id]);
    }

    /**
     * Aggregate payroll figures for a given month/year.
     *
     * @return array{employee_count:int, total_basic:float, total_allowances:float,
     *               total_deductions:float, total_overtime:float, total_tax:float, total_net:float}
     */
    private function getMonthlyPayrollSummary(int $month, int $year): array
    {
        $stmt = $this->db->prepare(<<<SQL
            SELECT
                COUNT(*)         AS employee_count,
                SUM(basic_salary) AS total_basic,
                SUM(allowances)   AS total_allowances,
                SUM(deductions)   AS total_deductions,
                SUM(overtime_pay) AS total_overtime,
                SUM(income_tax)   AS total_tax,
                SUM(net_salary)   AS total_net
            FROM payrolls
            WHERE period_month = :month AND period_year = :year
        SQL);

        $stmt->execute([':month' => $month, ':year' => $year]);
        $row = $stmt->fetch();

        return [
            'employee_count'   => (int)   ($row['employee_count']   ?? 0),
            'total_basic'      => (float) ($row['total_basic']      ?? 0),
            'total_allowances' => (float) ($row['total_allowances'] ?? 0),
            'total_deductions' => (float) ($row['total_deductions'] ?? 0),
            'total_overtime'   => (float) ($row['total_overtime']   ?? 0),
            'total_tax'        => (float) ($row['total_tax']        ?? 0),
            'total_net'        => (float) ($row['total_net']        ?? 0),
        ];
    }

    /**
     * Calculate Bangladesh individual income tax using FY 2023-24 slabs.
     *
     * Slabs (in BDT):
     *  0        – 3,50,000   →  0%
     *  3,50,001 – 4,50,000   →  5%
     *  4,50,001 – 7,50,000   → 10%
     *  7,50,001 – 11,50,000  → 15%
     *  11,50,001–16,50,000   → 20%
     *  Above 16,50,000       → 25%
     */
    private function calculateBangladeshIncomeTax(float $annualIncome): float
    {
        $tax = 0.0;

        $slabs = [
            [350_000,  0.00],
            [100_000,  0.05],
            [300_000,  0.10],
            [400_000,  0.15],
            [500_000,  0.20],
            [PHP_INT_MAX, 0.25],
        ];

        $remaining = $annualIncome;

        foreach ($slabs as [$slabSize, $rate]) {
            if ($remaining <= 0) {
                break;
            }

            $taxable    = min($remaining, $slabSize);
            $tax       += $taxable * $rate;
            $remaining -= $taxable;
        }

        return round($tax, 2);
    }
}

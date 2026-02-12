<?php

namespace App\Contracts;

/**
 * Fiscal Device Driver Interface
 *
 * Defines the contract for Macedonian fiscal device integrations.
 * Supported devices: Daisy FX 1300, Synergy PF-500, Expert SX, Severec.
 *
 * Each driver implements the vendor-specific protocol for communicating
 * with the fiscal printer/device over TCP/IP or serial connection.
 */
interface FiscalDeviceDriver
{
    /**
     * Connect to the fiscal device.
     *
     * @param array $config Device configuration (ip_address, port, serial_number, etc.)
     * @return bool True if connection was successful
     *
     * @throws \App\Exceptions\FiscalDeviceException
     */
    public function connect(array $config): bool;

    /**
     * Send an invoice to the fiscal device for fiscalization.
     *
     * @param array $invoiceData Invoice data including items, taxes, totals
     * @return array{fiscal_id: string, receipt_number: string, raw_response: string}
     *
     * @throws \App\Exceptions\FiscalDeviceException
     */
    public function sendInvoice(array $invoiceData): array;

    /**
     * Get the current status of the fiscal device.
     *
     * @return array{connected: bool, paper: bool, fiscal_memory: string, last_receipt: ?string, errors: array}
     *
     * @throws \App\Exceptions\FiscalDeviceException
     */
    public function getStatus(): array;

    /**
     * Get the last printed receipt data.
     *
     * @return array{receipt_number: string, amount: int, vat_amount: int, fiscal_id: string, timestamp: string}
     *
     * @throws \App\Exceptions\FiscalDeviceException
     */
    public function getLastReceipt(): array;

    /**
     * Get daily totals report (Z-report) from the device.
     *
     * @return array{total_amount: int, total_vat: int, receipt_count: int, report_number: string}
     *
     * @throws \App\Exceptions\FiscalDeviceException
     */
    public function getDailyReport(): array;

    /**
     * Disconnect from the fiscal device.
     *
     * @return void
     */
    public function disconnect(): void;

    /**
     * Get the driver name identifier.
     *
     * @return string e.g., 'daisy', 'synergy', 'expert', 'severec'
     */
    public function getDriverName(): string;
}
// CLAUDE-CHECKPOINT

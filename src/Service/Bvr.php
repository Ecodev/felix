<?php

declare(strict_types=1);

namespace Ecodev\Felix\Service;

use Exception;

/**
 * Class to generate BVR reference number and encoding lines.
 *
 * Typically, usage would one of the following:
 *
 * ```
 * <?php
 *
 * // Provided by your bank
 * $bankAccount = '800876';
 * $postAccount = '01-3456-0';
 *
 * // Your own custom ID to uniquely identify the payment
 * $myId = (string) $user->getId();
 *
 * $referenceNumberToCopyPasteInEBanking = Bvr::getReferenceNumber($bankAccount, $myId);
 * ```
 *
 * @see https://www.postfinance.ch/content/dam/pfch/doc/cust/download/inpayslip_isr_man_fr.pdf
 */
final class Bvr
{
    private const TABLE = [
        [0, 9, 4, 6, 8, 2, 7, 1, 3, 5],
        [9, 4, 6, 8, 2, 7, 1, 3, 5, 0],
        [4, 6, 8, 2, 7, 1, 3, 5, 0, 9],
        [6, 8, 2, 7, 1, 3, 5, 0, 9, 4],
        [8, 2, 7, 1, 3, 5, 0, 9, 4, 6],
        [2, 7, 1, 3, 5, 0, 9, 4, 6, 8],
        [7, 1, 3, 5, 0, 9, 4, 6, 8, 2],
        [1, 3, 5, 0, 9, 4, 6, 8, 2, 7],
        [3, 5, 0, 9, 4, 6, 8, 2, 7, 1],
        [5, 0, 9, 4, 6, 8, 2, 7, 1, 3],
    ];

    /**
     * Get the reference number, including the verification digit.
     */
    public static function getReferenceNumber(string $bankAccount, string $customId): string
    {
        if (!preg_match('~^\d{0,20}$~', $customId)) {
            throw new Exception('Invalid custom ID. It must be 20 or less digits, but got: `' . $customId . '`');
        }

        if (!preg_match('~^\d{6}$~', $bankAccount)) {
            throw new Exception('Invalid bank account. It must be exactly 6 digits, but got: `' . $bankAccount . '`');
        }
        $value = $bankAccount . self::pad($customId);

        return $value . self::modulo10($value);
    }

    /**
     * Extract the custom ID as string from a valid reference number.
     */
    public static function extractCustomId(string $referenceNumber): string
    {
        if (!preg_match('~^\d{27}$~', $referenceNumber)) {
            throw new Exception('Invalid reference number. It must be exactly 27 digits, but got: `' . $referenceNumber . '`');
        }
        $value = mb_substr($referenceNumber, 0, 26);
        $expectedVerificationDigit = (int) mb_substr($referenceNumber, 26, 27);
        $actualVerificationDigit = self::modulo10($value);
        if ($expectedVerificationDigit !== $actualVerificationDigit) {
            throw new Exception('Invalid reference number. The verification digit does not match. Expected `' . $expectedVerificationDigit . '`, but got `' . $actualVerificationDigit . '`');
        }

        return mb_substr($referenceNumber, 6, 20);
    }

    /**
     * Check if an IBAN is actually a valid Swiss QR-IBAN.
     */
    public static function isQrIban(string $iban): bool
    {
        if (!preg_match('/^CH[0-9]{2}([0-9]{5})[0-9A-Z]{12}$/', $iban, $m)) {
            return false;
        }

        $bankClearing = (int) $m[1];

        if ($bankClearing >= 30000 && $bankClearing <= 31199) {
            return true;
        }

        return false;
    }

    private static function pad(string $string): string
    {
        return str_pad($string, 20, '0', STR_PAD_LEFT);
    }

    /**
     * @internal
     */
    public static function modulo10(string $number): int
    {
        $report = 0;

        if ($number === '') {
            return $report;
        }

        $digits = mb_str_split($number);
        foreach ($digits as $value) {
            $report = self::TABLE[$report][(int) $value];
        }

        return (10 - $report) % 10;
    }
}

<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Service;

use Ecodev\Felix\Service\Bvr;
use PHPUnit\Framework\TestCase;

final class BvrTest extends TestCase
{
    /**
     * @dataProvider providerGetReferenceNumber
     */
    public function testGetReferenceNumber(string $bankAccount, string $referenceNumber, string $expected): void
    {
        $actual = Bvr::getReferenceNumber($bankAccount, $referenceNumber);
        self::assertSame($expected, $actual);
    }

    public static function providerGetReferenceNumber(): array
    {
        return [
            ['123456', '', '123456000000000000000000006'],
            ['123456', '789', '123456000000000000000007891'],
        ];
    }

    public function testGetReferenceNumberMustThrowIfTooLongBankAccount(): void
    {
        $this->expectExceptionMessage('Invalid bank account. It must be exactly 6 digits, but got: `1234567`');
        Bvr::getReferenceNumber('1234567', '123');
    }

    public function testGetReferenceNumberMustThrowIfTooLongReferenceNumber(): void
    {
        $this->expectExceptionMessage('Invalid custom ID. It must be 20 or less digits, but got: `000000000000000000000`');
        Bvr::getReferenceNumber('123456', str_repeat('0', 21));
    }

    public function testGetReferenceNumberMustThrowIfInvalidReferenceNumber(): void
    {
        $this->expectExceptionMessage('Invalid custom ID. It must be 20 or less digits, but got: `1.5`');
        Bvr::getReferenceNumber('123456', '1.5');
    }

    /**
     * @dataProvider providerModulo10
     */
    public function testModulo10(string $number, int $expected): void
    {
        $actual = Bvr::modulo10($number);
        self::assertSame($expected, $actual);
    }

    public static function providerModulo10(): array
    {
        return [
            ['', 0],
            ['0', 0],
            ['04', 2],
            ['010000394975', 3],
            ['313947143000901', 8],
            ['80082600000000000000000201', 6],
            ['80082600000000000000000001', 2],
            ['80082600000000000000000002', 8],
            ['80082600000000000000000003', 3],
            ['80082600000000000000000004', 9],
            ['80082600000000000000000005', 7],
            ['80082600000000000000000006', 5],
            ['80082600000000000000000007', 0],
            ['80082600000000000000000008', 1],
            ['80082600000000000000000009', 6],
            ['80082600000000000000000010', 8],
        ];
    }

    /**
     * @dataProvider providerExtractCustomId
     */
    public function testExtractCustomId(string $referenceNumber, string $expected): void
    {
        $actual = Bvr::extractCustomId($referenceNumber);
        self::assertSame($expected, $actual);
    }

    public static function providerExtractCustomId(): array
    {
        return [
            ['800826000000000000000002016', '00000000000000000201'],
            ['000000000000000000000000000', '00000000000000000000'],
            ['000000000000000000000001236', '00000000000000000123'],
        ];
    }

    public function testExtractCustomIdMustThrowIfInvalidReferenceNumber(): void
    {
        $this->expectExceptionMessage('Invalid reference number. It must be exactly 27 digits, but got: `foo`');
        Bvr::extractCustomId('foo');
    }

    public function testExtractCustomIdMustThrowIfInvalidVerificationDigit(): void
    {
        $this->expectExceptionMessage('Invalid reference number. The verification digit does not match. Expected `0`, but got `6`');
        Bvr::extractCustomId('800826000000000000000002010');
    }

    public static function providerIban(): array
    {
        return [
            ['30-12465-5', false],
            ['', false],
            ['CH2208390037471510005', false],
            ['CH7030123036078110002', true],
        ];
    }

    /**
     * @dataProvider providerIban
     */
    public function testQrIban(string $iban, bool $expected): void
    {
        $actual = Bvr::isQrIban($iban);
        self::assertSame($expected, $actual);
    }
}

<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Model\Traits;

use Ecodev\Felix\Model\Traits\HasOtp;
use PHPUnit\Framework\TestCase;

final class HasOtpTest extends TestCase
{
    private \Ecodev\Felix\Model\HasOtp $user;

    protected function setUp(): void
    {
        $this->user = new class() implements \Ecodev\Felix\Model\HasOtp {
            use HasOtp;

            public function getLogin(): ?string
            {
                return 'user@example.com';
            }
        };
    }

    public function testCreateOtpSecret(): void
    {
        self::assertNull($this->user->getOtpUri(), 'should have no OTP secret at first');
        self::assertFalse($this->user->isOtp(), 'should have OTP disabled at first');

        self::expectExceptionMessage('Cannot enable OTP without a secret');
        $this->user->setOtp(true);

        $this->user->createOtpSecret('felix.lan', 30, 'sha1', 6);
        $otp1 = $this->user->getOtpUri();
        self::assertIsString($otp1);
        self::assertStringStartsWith('otpauth://totp/', $otp1, '6 digits TOTP using SHA1 valid 30s');

        $this->user->createOtpSecret('felix.lan', 40, 'sha256', 8);
        $otp2 = $this->user->getOtpUri();
        self::assertIsString($otp2);
        self::assertStringStartsWith('otpauth://totp/', $otp2, '8 digits TOTP using SHA256 valid 40s');
        self::assertNotSame($otp1, $otp2, 'user OTP URI must have been changed');

        $this->user->setOtp(true);
        self::assertTrue($this->user->isOtp());
    }

    public function testRevokeSecret(): void
    {
        $this->user->createOtpSecret('felix.lan');
        $this->user->revokeOtpSecret();

        self::assertFalse($this->user->isOtp());
        self::assertNull($this->user->getOtpUri());
    }
}

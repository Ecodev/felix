<?php

declare(strict_types=1);

namespace EcodevTests\Felix\Model\Traits;

use Ecodev\Felix\Model\Traits\HasOtp;
use OTPHP\Factory;
use OTPHP\TOTPInterface;
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

        $this->user->createOtpSecret('felix.lan');
        $otp1 = $this->user->getOtpUri();
        self::assertIsString($otp1);
        self::assertStringStartsWith('otpauth://totp/', $otp1, 'TOTP provisionning URI was generated and stored');

        $this->user->createOtpSecret('felix.lan');
        $otp2 = $this->user->getOtpUri();
        self::assertIsString($otp2);
        self::assertNotSame($otp1, $otp2, 'TOTP provisionning URI was changed');

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

    public function testVerifySecret(): void
    {
        $this->user->setOtp(false);
        self::assertFalse($this->user->verifyOtp('123456'), 'Cannot verify OTP with 2FA disabled');

        $this->user->createOtpSecret('felix.lan');
        $this->user->setOtp(true);

        self::assertFalse($this->user->verifyOtp('123456'), 'Wrong OTP given');

        $uri = $this->user->getOtpUri();
        self::assertNotNull($uri);

        $otp = Factory::loadFromProvisioningUri($uri);
        self::assertInstanceOf(TOTPInterface::class, $otp);
        self::assertTrue($this->user->verifyOtp($otp->now()), 'Correct OTP given');
    }
}

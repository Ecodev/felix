<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model;

/**
 * Interface for a user with second factor authentication using OTP.
 */
interface HasOtp
{
    /**
     * Enable 2FA for the user
     * This should be only enabled after otpUri has been generated, stored and verified.
     */
    public function setOtp(bool $otp): void;

    /**
     * Whether the user has 2FA enabled.
     */
    public function isOtp(): bool;

    /**
     * Returns the OTP provisioning URI (to display QR code).
     */
    public function getOtpUri(): ?string;

    /**
     * Generate and store a new OTP secret.
     */
    public function createOtpSecret(string $issuer): void;

    /**
     * Revoke the existing OTP secret.
     */
    public function revokeOtpSecret(): void;

    /**
     * Verify an OTP received from the user.
     */
    public function verifyOtp(string $received): bool;
}

<?php

declare(strict_types=1);

namespace Ecodev\Felix\Model\Traits;

use Doctrine\ORM\Mapping as ORM;
use Exception;
use GraphQL\Doctrine\Annotation as API;
use OTPHP;

/**
 * Trait for a user with second factor authentication using OTP.
 */
trait HasOtp
{
    /**
     * @ORM\Column(type="boolean", options={"default" = 0})
     */
    private bool $otp = false;

    /**
     * @API\Exclude
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $otpUri = null;

    /**
     * Must be implemented by the user class to use the login as the key label.
     */
    abstract public function getLogin(): ?string;

    /**
     * Enable 2FA for the user
     * This should be only enabled after otpUri has been generated, stored and verified.
     *
     * @API\Exclude
     */
    public function setOtp(bool $otp): void
    {
        if ($otp && empty($this->otpUri)) {
            throw new Exception('Cannot enable OTP without a secret');
        }

        $this->otp = $otp;
    }

    /**
     * Whether the user has 2FA enabled.
     */
    public function isOtp(): bool
    {
        return $this->otp;
    }

    /**
     * Returns the OTP provisionning URI (to display QR code).
     *
     * @API\Exclude
     */
    public function getOtpUri(): ?string
    {
        return $this->otpUri;
    }

    /**
     * Generate and store a new OTP secret.
     *
     * @param string $issuer identify the service that provided the OTP (application or host name)
     *
     * @API\Exclude
     */
    public function createOtpSecret(string $issuer): void
    {
        $this->revokeOtpSecret();

        $totp = OTPHP\TOTP::create(null);
        $label = $this->getLogin();
        if (!$label) {
            throw new Exception('User must have a login to initialize OTP');
        }
        $totp->setLabel($label);
        $totp->setIssuer($issuer);
        $this->otpUri = $totp->getProvisioningUri();
    }

    /**
     * Revoke the existing OTP secret
     * This will also disable 2FA.
     *
     * @API\Exclude
     */
    public function revokeOtpSecret(): void
    {
        $this->otp = false;
        $this->otpUri = null;
    }

    /**
     * Verify an OTP received from the user.
     *
     * @API\Exclude
     */
    public function verifyOtp(string $received): bool
    {
        if (empty($this->otpUri)) {
            return false;
        }
        $otp = OTPHP\Factory::loadFromProvisioningUri($this->otpUri);

        return $otp->verify($received);
    }
}

<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Output;

use GraphQL\Type\Definition\ObjectType;

final class UpdateUserOtp extends ObjectType
{
    public function __construct()
    {
        $config = [
            'description' => 'Result of a 2FA activation/deactivation',
            'fields' => [
                'status' => [
                    'type' => self::int(),
                    'description' => '0: nothing to be done, 1: secret generated (step 1), 2: activation success (step 2), 3: deactivation success',
                ],
                'qrcode' => [
                    'type' => self::string(),
                    'description' => 'Provisioning QR code in SVG',
                ],
            ],
        ];

        parent::__construct($config);
    }
}

<?php

declare(strict_types=1);

namespace Ecodev\Felix\Api\Scalar;

use GraphQL\Language\AST\Node;

class CountryCode2Type extends AbstractStringBasedType
{
    public ?string $description = 'ISO 3166-1 alpha-2 two letter country code';

    public const countryCodes = [
        'AD', // Andorra
        'AE', // United Arab Emirates
        'AF', // Afghanistan
        'AG', // Antigua and Barbuda
        'AI', // Anguilla
        'AL', // Albania
        'AM', // Armenia
        'AO', // Angola
        'AQ', // Antarctica
        'AR', // Argentina
        'AS', // American Samoa
        'AT', // Austria
        'AU', // Australia
        'AW', // Aruba
        'AX', // Åland Islands
        'AZ', // Azerbaijan
        'BA', // Bosnia and Herzegovina
        'BB', // Barbados
        'BD', // Bangladesh
        'BE', // Belgium
        'BF', // Burkina Faso
        'BG', // Bulgaria
        'BH', // Bahrain
        'BI', // Burundi
        'BJ', // Benin
        'BL', // Saint Barthélemy
        'BM', // Bermuda
        'BN', // Brunei Darussalam
        'BO', // Bolivia (Plurinational State of)
        'BQ', // Bonaire, Sint Eustatius and Saba
        'BR', // Brazil
        'BS', // Bahamas
        'BT', // Bhutan
        'BV', // Bouvet Island
        'BW', // Botswana
        'BY', // Belarus
        'BZ', // Belize
        'CA', // Canada
        'CC', // Cocos (Keeling) Islands
        'CD', // Congo (Democratic Republic of the)
        'CF', // Central African Republic
        'CG', // Congo
        'CH', // Switzerland
        'CI', // Côte d'Ivoire
        'CK', // Cook Islands
        'CL', // Chile
        'CM', // Cameroon
        'CN', // China
        'CO', // Colombia
        'CR', // Costa Rica
        'CU', // Cuba
        'CV', // Cabo Verde
        'CW', // Curaçao
        'CX', // Christmas Island
        'CY', // Cyprus
        'CZ', // Czech Republic
        'DE', // Germany
        'DJ', // Djibouti
        'DK', // Denmark
        'DM', // Dominica
        'DO', // Dominican Republic
        'DZ', // Algeria
        'EC', // Ecuador
        'EE', // Estonia
        'EG', // Egypt
        'EH', // Western Sahara
        'ER', // Eritrea
        'ES', // Spain
        'ET', // Ethiopia
        'FI', // Finland
        'FJ', // Fiji
        'FK', // Falkland Islands (Malvinas)
        'FM', // Micronesia (Federated States of)
        'FO', // Faroe Islands
        'FR', // France
        'GA', // Gabon
        'GB', // United Kingdom of Great Britain and Northern Ireland
        'GD', // Grenada
        'GE', // Georgia
        'GF', // French Guiana
        'GG', // Guernsey
        'GH', // Ghana
        'GI', // Gibraltar
        'GL', // Greenland
        'GM', // Gambia
        'GN', // Guinea
        'GP', // Guadeloupe
        'GQ', // Equatorial Guinea
        'GR', // Greece
        'GS', // South Georgia and the South Sandwich Islands
        'GT', // Guatemala
        'GU', // Guam
        'GW', // Guinea-Bissau
        'GY', // Guyana
        'HK', // Hong Kong
        'HM', // Heard Island and McDonald Islands
        'HN', // Honduras
        'HR', // Croatia
        'HT', // Haiti
        'HU', // Hungary
        'ID', // Indonesia
        'IE', // Ireland
        'IL', // Israel
        'IM', // Isle of Man
        'IN', // India
        'IO', // British Indian Ocean Territory
        'IQ', // Iraq
        'IR', // Iran (Islamic Republic of)
        'IS', // Iceland
        'IT', // Italy
        'JE', // Jersey
        'JM', // Jamaica
        'JO', // Jordan
        'JP', // Japan
        'KE', // Kenya
        'KG', // Kyrgyzstan
        'KH', // Cambodia
        'KI', // Kiribati
        'KM', // Comoros
        'KN', // Saint Kitts and Nevis
        'KP', // Korea (Democratic People's Republic of)
        'KR', // Korea (Republic of)
        'KW', // Kuwait
        'KY', // Cayman Islands
        'KZ', // Kazakhstan
        'LA', // Lao People's Democratic Republic
        'LB', // Lebanon
        'LC', // Saint Lucia
        'LI', // Liechtenstein
        'LK', // Sri Lanka
        'LR', // Liberia
        'LS', // Lesotho
        'LT', // Lithuania
        'LU', // Luxembourg
        'LV', // Latvia
        'LY', // Libya
        'MA', // Morocco
        'MC', // Monaco
        'MD', // Moldova (Republic of)
        'ME', // Montenegro
        'MF', // Saint Martin (French part)
        'MG', // Madagascar
        'MH', // Marshall Islands
        'MK', // North Macedonia
        'ML', // Mali
        'MM', // Myanmar
        'MN', // Mongolia
        'MO', // Macao
        'MP', // Northern Mariana Islands
        'MQ', // Martinique
        'MR', // Mauritania
        'MS', // Montserrat
        'MT', // Malta
        'MU', // Mauritius
        'MV', // Maldives
        'MW', // Malawi
        'MX', // Mexico
        'MY', // Malaysia
        'MZ', // Mozambique
        'NA', // Namibia
        'NC', // New Caledonia
        'NE', // Niger
        'NF', // Norfolk Island
        'NG', // Nigeria
        'NI', // Nicaragua
        'NL', // Netherlands
        'NO', // Norway
        'NP', // Nepal
        'NR', // Nauru
        'NU', // Niue
        'NZ', // New Zealand
        'OM', // Oman
        'PA', // Panama
        'PE', // Peru
        'PF', // French Polynesia
        'PG', // Papua New Guinea
        'PH', // Philippines
        'PK', // Pakistan
        'PL', // Poland
        'PM', // Saint Pierre and Miquelon
        'PN', // Pitcairn
    ];

    /**
     * Parse lowercase value and convert to standard uppercase.
     */
    public function parseValue(mixed $value): ?string
    {
        $parsed = parent::parseValue($value);
        if (is_string($parsed)) {
            $parsed = mb_strtoupper($parsed);
        }

        return $parsed;
    }

    /**
     * Parse literal lowercase and convert to standard uppercase.
     */
    public function parseLiteral(Node $valueNode, ?array $variables = null): ?string
    {
        $parsed = parent::parseLiteral($valueNode, $variables);
        if (is_string($parsed)) {
            $parsed = mb_strtoupper($parsed);
        }

        return $parsed;
    }

    /**
     * Serializes an internal value and convert to uppercase.
     */
    public function serialize(mixed $value): mixed
    {
        // Assuming internal representation is always correct:
        if (is_string($value)) {
            $value = mb_strtoupper($value);
        }

        return $value;
    }

    /**
     * Validate a country code.
     */
    protected function isValid(?string $value): bool
    {
        return is_string($value) && mb_strlen($value) === 2 && in_array(mb_strtoupper($value), self::countryCodes, true);
    }
}

<?php

declare(strict_types=1);

namespace Ecodev\Felix\ORM\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;
use Exception;

/**
 * A custom DQL function to be able to use `IN` clause but with native SQL sub-queries.
 *
 * This is especially useful when we want to benefit from DQL builder, paginator,
 * automatic ACL filter etc., but still have to have some advanced conditions in sub-queries.
 *
 * DQL must not be handwritten, but instead `self::dql()` should be used
 */
class NativeIn extends FunctionNode
{
    private string|Node $field;

    private Literal $nativeQuery;

    private Literal $isNot;

    /**
     * Generate DQL `IN` clause with a native sub-query.
     *
     * @param string $field DQL for the field
     * @param string $nativeSql native SQL sub-query
     */
    public static function dql(string $field, string $nativeSql, bool $isNot = false): string
    {
        $quotedNativeSql = "'" . str_replace("'", "''", $nativeSql) . "'";

        return 'NATIVE_IN(' . $field . ',   ' . $quotedNativeSql . ', ' . (int) $isNot . ') = TRUE';
    }

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->field = $parser->ArithmeticPrimary();
        $parser->match(TokenType::T_COMMA);

        $this->nativeQuery = $parser->Literal();
        $parser->match(TokenType::T_COMMA);

        $this->isNot = $parser->Literal();

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $field = is_string($this->field) ? $sqlWalker->walkResultVariable($this->field) : $this->field->dispatch($sqlWalker);
        $nativeSql = $this->nativeQuery->dispatch($sqlWalker);
        $nativeSql = preg_replace("~^'(.*)'$~", '\1', $nativeSql);
        if ($nativeSql === null) {
            throw new Exception('Error while unquoting native SQL');
        }

        $unquotedNativeSql = str_replace(["\\'", '\n'], ["'", "\n"], $nativeSql);

        $isNot = $this->isNot->dispatch($sqlWalker);

        $sql = $field . ($isNot ? ' NOT' : '') . ' IN (' . $unquotedNativeSql . ')';

        return $sql;
    }
}

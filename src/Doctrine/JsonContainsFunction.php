<?php

declare(strict_types=1);

namespace App\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * "JSON_CONTAINS" "(" StringPrimary "," StringPrimary ["," StringPrimary] ")"
 *
 * Bildet die MySQL-Funktion JSON_CONTAINS(target, candidate[, path]) in DQL ab.
 * Doctrine ORM kennt sie nicht von Haus aus – ohne diese Registrierung wirft
 * der `?lang_…`-Filter (RestaurantRepository::findPaginated) eine QueryException.
 */
final class JsonContainsFunction extends FunctionNode
{
    private Node $target;
    private Node $candidate;
    private ?Node $path = null;

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->target = $parser->StringPrimary();
        $parser->match(TokenType::T_COMMA);
        $this->candidate = $parser->StringPrimary();

        if ($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);
            $this->path = $parser->StringPrimary();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $sql = 'JSON_CONTAINS('
            .$sqlWalker->walkStringPrimary($this->target).', '
            .$sqlWalker->walkStringPrimary($this->candidate);

        if ($this->path !== null) {
            $sql .= ', '.$sqlWalker->walkStringPrimary($this->path);
        }

        return $sql.')';
    }
}

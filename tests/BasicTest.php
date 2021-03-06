<?php

declare(strict_types=1);

namespace Cloudstek\SCIM\FilterParser\Tests;

use Cloudstek\SCIM\FilterParser\AST;
use Cloudstek\SCIM\FilterParser\FilterParser;
use Cloudstek\SCIM\FilterParser\FilterParserInterface;
use Cloudstek\SCIM\FilterParser\Tokenizer;
use Nette\Tokenizer\Token;
use PHPUnit\Framework\TestCase;

class BasicTest extends TestCase
{
    public function testInstantiate()
    {
        $parser = new FilterParser();

        $this->assertInstanceOf(FilterParserInterface::class, $parser);
        $this->assertInstanceOf(FilterParser::class, $parser);
    }

    public function testEmptyNegationReturnsNull()
    {
        $parser = new FilterParser();

        $node = $parser->parse('not ()');

        $this->assertNull($node);
    }

    public function testEmptyGroupingReturnsNull()
    {
        $parser = new FilterParser();

        $node = $parser->parse('()');

        $this->assertNull($node);
    }

    public function testUnexpectedTypeAtStart()
    {
        $parser = new FilterParser();

        $this->expectException(\Nette\Tokenizer\Exception::class);
        $this->expectExceptionMessage(
            'Expected an attribute/value path, opening parenthesis or a negation, got " and ".'
        );

        $parser->parse(' and userName eq "foobar"');
    }

    public function testStreamMatchNextUnexpectedEndOfString()
    {
        $this->expectException(\Nette\Tokenizer\Exception::class);
        $this->expectExceptionMessage('Unexpected end of string.');

        $tokens = [
            new Token('userName', 1, 0),
            new Token(' eq ', 2, 8),
        ];

        $stream = new Tokenizer\Stream($tokens);

        $stream->matchNext(1);
        $stream->matchNext(2);
        $stream->matchNext(3);
    }

    public function testStreamMatchNextMismatch()
    {
        $this->expectException(\Nette\Tokenizer\Exception::class);
        $this->expectExceptionMessage('Unexpected "eq" on line 1, column 9.');

        $tokens = [
            new Token('userName', 1, 0),
            new Token(' eq ', 2, 8),
        ];

        $stream = new Tokenizer\Stream($tokens);

        $stream->matchNext(1);
        $stream->matchNext(3);
    }

    public function testHasParent()
    {
        $nodes = [
            new AST\Comparison(new AST\AttributePath(null, ['foo', 'bar']), AST\Operator::EQ(), 'baz'),
            new AST\Comparison(new AST\AttributePath(null, ['baz']), AST\Operator::PR(), null)
        ];

        $conjunction = new AST\Conjunction($nodes, null);

        $negation = new AST\Negation($conjunction);

        $this->assertFalse($negation->hasParent());
        $this->assertTrue($conjunction->hasParent());
        $this->assertTrue($conjunction[0]->hasParent());
        $this->assertTrue($conjunction[1]->hasParent());
    }

    public function testHasParentFqcn()
    {
        $nodes = [
            new AST\Comparison(new AST\AttributePath(null, ['foo', 'bar']), AST\Operator::EQ(), 'baz'),
            new AST\Comparison(new AST\AttributePath(null, ['baz']), AST\Operator::PR(), null)
        ];

        $conjunction = new AST\Conjunction($nodes, null);

        $negation = new AST\Negation($conjunction);

        $this->assertFalse($negation->hasParent(AST\Negation::class));
        $this->assertTrue($conjunction->hasParent(AST\Negation::class));
        $this->assertFalse($conjunction[0]->hasParent(AST\Negation::class));
        $this->assertFalse($conjunction[1]->hasParent(AST\Negation::class));
    }

    public function testHasParentInstance()
    {
        $nodes = [
            new AST\Comparison(new AST\AttributePath(null, ['foo', 'bar']), AST\Operator::EQ(), 'baz'),
            new AST\Comparison(new AST\AttributePath(null, ['baz']), AST\Operator::PR(), null)
        ];

        $conjunction = new AST\Conjunction($nodes, null);

        $negation = new AST\Negation($conjunction);

        $this->assertFalse($negation->hasParent($negation));
        $this->assertTrue($conjunction->hasParent($negation));
        $this->assertFalse($conjunction[0]->hasParent($negation));
        $this->assertFalse($conjunction[1]->hasParent($negation));
    }

    public function testHasParentRecursive()
    {
        $nodes = [
            new AST\Comparison(new AST\AttributePath(null, ['foo', 'bar']), AST\Operator::EQ(), 'baz'),
            new AST\Comparison(new AST\AttributePath(null, ['baz']), AST\Operator::PR(), null)
        ];

        $conjunction = new AST\Conjunction($nodes, null);

        $negation = new AST\Negation($conjunction);

        $this->assertFalse($negation->hasParent());
        $this->assertTrue($conjunction->hasParent(null, true));
        $this->assertTrue($conjunction[0]->hasParent(null, true));
        $this->assertTrue($conjunction[1]->hasParent(null, true));
    }

    public function testHasParentFqcnRecursive()
    {
        $nodes = [
            new AST\Comparison(new AST\AttributePath(null, ['foo', 'bar']), AST\Operator::EQ(), 'baz'),
            new AST\Comparison(new AST\AttributePath(null, ['baz']), AST\Operator::PR(), null)
        ];

        $conjunction = new AST\Conjunction($nodes, null);

        $negation = new AST\Negation($conjunction);

        $this->assertFalse($negation->hasParent(AST\Negation::class, true));
        $this->assertTrue($conjunction->hasParent(AST\Negation::class, true));
        $this->assertTrue($conjunction[0]->hasParent(AST\Negation::class, true));
        $this->assertTrue($conjunction[1]->hasParent(AST\Negation::class, true));
    }
}

<?php

declare(strict_types=1);

namespace MensCircle\Rector\CodingStyle\Rector;

use PhpParser\Node;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\InterpolatedStringPart;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\Node\Scalar\String_;
use Rector\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * Converts sprintf() calls and string concatenations back to interpolated strings.
 *
 * This is the reverse of EncapsedStringsToSprintfRector.
 * Only converts simple variables, property fetches, and array accesses - not complex expressions.
 *
 * @see \Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector
 */
final class SprintfToEncapsedStringsRector extends AbstractRector
{
    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition(
            'Convert sprintf() and string concatenation to interpolated strings',
            [
                new CodeSample(
                    <<<'CODE_SAMPLE'
echo sprintf('Hello %s, you have %d messages', $name, $count);
echo 'Hello ' . $name;
CODE_SAMPLE
                    ,
                    <<<'CODE_SAMPLE'
echo "Hello {$name}, you have {$count} messages";
echo "Hello {$name}";
CODE_SAMPLE
                ),
            ]
        );
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [FuncCall::class, Concat::class];
    }

    /**
     * @param FuncCall|Concat $node
     */
    public function refactor(Node $node): ?Node
    {
        if ($node instanceof FuncCall) {
            return $this->refactorSprintfCall($node);
        }

        return $this->refactorConcat($node);
    }

    private function refactorSprintfCall(FuncCall $funcCall): ?InterpolatedString
    {
        if (!$this->isName($funcCall, 'sprintf')) {
            return null;
        }

        $args = $funcCall->getArgs();
        if (count($args) < 2) {
            return null;
        }

        $formatArg = $args[0]->value;
        if (!$formatArg instanceof String_) {
            return null;
        }

        $format = $formatArg->value;

        // Skip complex format specifiers (only allow simple %s and %d)
        if ($this->hasComplexFormatSpecifiers($format)) {
            return null;
        }

        // Skip if format contains special characters that don't work well in interpolated strings
        if ($this->containsProblematicCharacters($format)) {
            return null;
        }

        $variables = [];
        foreach (array_slice($args, 1) as $arg) {
            $resolved = $this->resolveVariable($arg);
            if ($resolved === null) {
                return null;
            }
            $variables[] = $resolved;
        }

        return $this->createInterpolatedString($format, $variables);
    }

    private function refactorConcat(Concat $concat): ?InterpolatedString
    {
        $parts = $this->flattenConcat($concat);

        // Only convert if there's at least one simple variable and one string
        $hasSimpleVariable = false;
        $hasString = false;

        foreach ($parts as $part) {
            if ($part instanceof String_) {
                $hasString = true;
            } elseif ($this->isSimpleInterpolatableExpr($part)) {
                $hasSimpleVariable = true;
            } else {
                // Contains complex expression, skip
                return null;
            }
        }

        if (!$hasSimpleVariable || !$hasString) {
            return null;
        }

        // Skip if any string part contains problematic characters
        foreach ($parts as $part) {
            if ($part instanceof String_ && $this->containsProblematicCharacters($part->value)) {
                return null;
            }
        }

        return $this->createInterpolatedStringFromParts($parts);
    }

    /**
     * @return array<Expr>
     */
    private function flattenConcat(Concat $concat): array
    {
        $parts = [];

        $left = $concat->left;
        if ($left instanceof Concat) {
            $parts = [...$parts, ...$this->flattenConcat($left)];
        } else {
            $parts[] = $left;
        }

        $right = $concat->right;
        if ($right instanceof Concat) {
            $parts = [...$parts, ...$this->flattenConcat($right)];
        } else {
            $parts[] = $right;
        }

        return $parts;
    }

    private function hasComplexFormatSpecifiers(string $format): bool
    {
        // Match complex format specifiers like %02d, %.2f, %10s, etc.
        // Only allow simple %s and %d
        $pattern = '/%(?!\%)[^sd]|%[0-9.+-]+[sd]/';

        return (bool) preg_match($pattern, $format);
    }

    private function containsProblematicCharacters(string $value): bool
    {
        // Skip strings with control characters (except \n and \t)
        if ((bool) preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', $value)) {
            return true;
        }

        // Skip strings that already contain { or } as they might conflict
        if (str_contains($value, '{') || str_contains($value, '}')) {
            return true;
        }

        // Skip strings with $ that could be misinterpreted as variables
        if (str_contains($value, '$')) {
            return true;
        }

        return false;
    }

    /**
     * Check if an expression is simple enough to be interpolated cleanly.
     * Only allows: $var, $obj->prop, $arr['key'], $arr[$var]
     */
    private function isSimpleInterpolatableExpr(Expr $expr): bool
    {
        // Simple variable: $name
        if ($expr instanceof Variable) {
            return true;
        }

        // Property fetch: $obj->property (only if base is a simple variable)
        if ($expr instanceof PropertyFetch) {
            return $expr->var instanceof Variable;
        }

        // Array access: $arr['key'] or $arr[$var] (only if base is a simple variable)
        if ($expr instanceof ArrayDimFetch) {
            return $expr->var instanceof Variable;
        }

        // PHP_EOL constant
        if ($expr instanceof ConstFetch && $this->isName($expr, 'PHP_EOL')) {
            return true;
        }

        return false;
    }

    private function resolveVariable(Arg $arg): ?Expr
    {
        $value = $arg->value;

        // Convert PHP_EOL constant to actual newline
        if ($value instanceof ConstFetch && $this->isName($value, 'PHP_EOL')) {
            return new String_("\n");
        }

        // Only allow simple interpolatable expressions
        if (!$this->isSimpleInterpolatableExpr($value)) {
            return null;
        }

        return $value;
    }

    /**
     * @param array<Expr> $variables
     */
    private function createInterpolatedString(string $format, array $variables): ?InterpolatedString
    {
        $parts = [];
        $variableIndex = 0;

        // Replace %% with a placeholder to avoid confusion
        $format = str_replace('%%', "\x00PERCENT\x00", $format);

        // Split by %s and %d
        $segments = preg_split('/(%[sd])/', $format, -1, PREG_SPLIT_DELIM_CAPTURE);
        if ($segments === false) {
            return null;
        }

        foreach ($segments as $segment) {
            if ($segment === '%s' || $segment === '%d') {
                if (!isset($variables[$variableIndex])) {
                    return null;
                }

                $variable = $variables[$variableIndex];
                $variableIndex++;

                // Handle string literals (like converted PHP_EOL)
                if ($variable instanceof String_) {
                    $parts[] = new InterpolatedStringPart($variable->value);
                } else {
                    $parts[] = $variable;
                }
            } elseif ($segment !== '') {
                // Restore %% as single %
                $segment = str_replace("\x00PERCENT\x00", '%', $segment);
                $parts[] = new InterpolatedStringPart($segment);
            }
        }

        // Check if all variables were used
        if ($variableIndex !== count($variables)) {
            return null;
        }

        if ($parts === []) {
            return null;
        }

        return new InterpolatedString($parts);
    }

    /**
     * @param array<Expr> $parts
     */
    private function createInterpolatedStringFromParts(array $parts): InterpolatedString
    {
        $interpolatedParts = [];

        foreach ($parts as $part) {
            if ($part instanceof String_) {
                $interpolatedParts[] = new InterpolatedStringPart($part->value);
            } elseif ($part instanceof ConstFetch && $this->isName($part, 'PHP_EOL')) {
                $interpolatedParts[] = new InterpolatedStringPart("\n");
            } else {
                $interpolatedParts[] = $part;
            }
        }

        return new InterpolatedString($interpolatedParts);
    }
}

<?php

declare(strict_types=1);

namespace MensCircle\PhpCsFixer\Fixer;

use PhpCsFixer\AbstractFixer;
use PhpCsFixer\Fixer\ConfigurableFixerInterface;
use PhpCsFixer\Fixer\ConfigurableFixerTrait;
use PhpCsFixer\Fixer\WhitespacesAwareFixerInterface;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolver;
use PhpCsFixer\FixerConfiguration\FixerConfigurationResolverInterface;
use PhpCsFixer\FixerConfiguration\FixerOptionBuilder;
use PhpCsFixer\FixerDefinition\CodeSample;
use PhpCsFixer\FixerDefinition\FixerDefinition;
use PhpCsFixer\FixerDefinition\FixerDefinitionInterface;
use PhpCsFixer\Preg;
use PhpCsFixer\Tokenizer\Token;
use PhpCsFixer\Tokenizer\Tokens;
use SplFileInfo;

final class ExtensionHeaderCommentFixer extends AbstractFixer implements ConfigurableFixerInterface, WhitespacesAwareFixerInterface
{
    use ConfigurableFixerTrait;

    public const HEADER_PHPDOC = 'PHPDoc';

    public const HEADER_COMMENT = 'comment';

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $composerDataCache = [];

    public function getDefinition(): FixerDefinitionInterface
    {
        return new FixerDefinition(
            'Add header comment based on extension composer.json.',
            [
                new CodeSample(
                    <<<'PHP'
                        <?php

                        declare(strict_types=1);

                        namespace Vendor\Extension;

                        class Foo {}

                        PHP,
                    [
                        'packages_path' => 'packages',
                        'header_template' => "This file is part of the {{name}} extension.\n\n(c) {{author}}",
                    ]
                ),
            ]
        );
    }

    public function isCandidate(Tokens $tokens): bool
    {
        return $tokens->isMonolithicPhp() && !$tokens->isTokenKindFound(T_OPEN_TAG_WITH_ECHO);
    }

    /**
     * Must run after DeclareStrictTypesFixer, NoBlankLinesAfterPhpdocFixer.
     * Must run before BlankLinesBeforeNamespaceFixer, SingleBlankLineBeforeNamespaceFixer.
     */
    public function getPriority(): int
    {
        return -30;
    }

    public function getName(): string
    {
        return 'MensCircle/extension_header_comment';
    }

    protected function applyFix(SplFileInfo $file, Tokens $tokens): void
    {
        $composerData = $this->findComposerDataForFile($file);
        if ($composerData === null) {
            return;
        }

        $header = $this->buildHeader($composerData);
        if ($header === '') {
            return;
        }

        $headerAsComment = $this->getHeaderAsComment($header);
        $location = $this->configuration['location'];

        $locationIndices = [];
        foreach (['after_open', 'after_declare_strict'] as $possibleLocation) {
            $locationIndex = $this->findHeaderCommentInsertionIndex($tokens, $possibleLocation);
            if (!isset($locationIndices[$locationIndex]) || $possibleLocation === $location) {
                $locationIndices[$locationIndex] = $possibleLocation;
            }
        }

        foreach ($locationIndices as $possibleLocation) {
            $headerNewIndex = $this->findHeaderCommentInsertionIndex($tokens, $possibleLocation);
            $headerCurrentIndex = $this->findHeaderCommentCurrentIndex($tokens, $headerNewIndex - 1);

            if ($headerCurrentIndex === null) {
                if ($possibleLocation !== $location) {
                    continue;
                }
                $this->insertHeader($tokens, $headerAsComment, $headerNewIndex);
                continue;
            }

            $currentHeaderComment = $tokens[$headerCurrentIndex]->getContent();
            $sameComment = $headerAsComment === $currentHeaderComment;
            $expectedLocation = $possibleLocation === $location;

            if (!$sameComment || !$expectedLocation) {
                if ($expectedLocation xor $sameComment) {
                    $this->removeHeader($tokens, $headerCurrentIndex);
                }

                if ($possibleLocation === $location) {
                    $this->insertHeader($tokens, $headerAsComment, $headerNewIndex);
                }
                continue;
            }

            $this->fixWhiteSpaceAroundHeader($tokens, $headerCurrentIndex);
        }
    }

    protected function createConfigurationDefinition(): FixerConfigurationResolverInterface
    {
        return new FixerConfigurationResolver([
            (new FixerOptionBuilder('packages_path', 'Path to packages directory relative to project root.'))
                ->setAllowedTypes(['string'])
                ->setDefault('packages')
                ->getOption(),
            (new FixerOptionBuilder('header_template', 'Header template with placeholders: {{name}}, {{description}}, {{homepage}}, {{license}}, {{author}}, {{author_name}}, {{author_email}}, {{extension_key}}'))
                ->setAllowedTypes(['string'])
                ->setDefault("This file is part of the {{name}} extension.\n\n(c) {{author}}")
                ->getOption(),
            (new FixerOptionBuilder('comment_type', 'Comment syntax type.'))
                ->setAllowedValues([self::HEADER_PHPDOC, self::HEADER_COMMENT])
                ->setDefault(self::HEADER_COMMENT)
                ->getOption(),
            (new FixerOptionBuilder('location', 'The location of the inserted header.'))
                ->setAllowedValues(['after_open', 'after_declare_strict'])
                ->setDefault('after_declare_strict')
                ->getOption(),
            (new FixerOptionBuilder('separate', 'Whether the header should be separated from the file content with a new line.'))
                ->setAllowedValues(['both', 'top', 'bottom', 'none'])
                ->setDefault('both')
                ->getOption(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findComposerDataForFile(SplFileInfo $file): ?array
    {
        $filePath = $file->getRealPath();
        if ($filePath === false) {
            return null;
        }

        $directory = dirname($filePath);

        while ($directory !== '' && $directory !== '/') {
            $composerJsonPath = $directory . '/composer.json';

            if (isset($this->composerDataCache[$composerJsonPath])) {
                return $this->composerDataCache[$composerJsonPath];
            }

            if (file_exists($composerJsonPath)) {
                $content = file_get_contents($composerJsonPath);
                if ($content === false) {
                    $directory = dirname($directory);
                    continue;
                }

                $data = json_decode($content, true);
                if (!is_array($data)) {
                    $directory = dirname($directory);
                    continue;
                }

                $type = $data['type'] ?? '';
                if ($type === 'typo3-cms-extension') {
                    $this->composerDataCache[$composerJsonPath] = $data;
                    return $data;
                }
            }

            $directory = dirname($directory);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $composerData
     */
    private function buildHeader(array $composerData): string
    {
        $template = $this->configuration['header_template'];

        $authorName = '';
        $authorEmail = '';
        $authorString = '';

        if (isset($composerData['authors']) && is_array($composerData['authors']) && $composerData['authors'] !== []) {
            $firstAuthor = $composerData['authors'][0];
            $authorName = $firstAuthor['name'] ?? '';
            $authorEmail = $firstAuthor['email'] ?? '';
            $authorString = $authorName;
            if ($authorEmail !== '') {
                $authorString .= $authorString !== '' ? " <{$authorEmail}>" : $authorEmail;
            }
        }

        $extensionKey = '';
        if (isset($composerData['extra']['typo3/cms']['extension-key'])) {
            $extensionKey = $composerData['extra']['typo3/cms']['extension-key'];
        }

        $license = '';
        if (isset($composerData['license'])) {
            $license = is_array($composerData['license'])
                ? implode(', ', $composerData['license'])
                : $composerData['license'];
        }

        $replacements = [
            '{{name}}' => $composerData['name'] ?? '',
            '{{description}}' => $composerData['description'] ?? '',
            '{{homepage}}' => $composerData['homepage'] ?? '',
            '{{license}}' => $license,
            '{{author}}' => $authorString,
            '{{author_name}}' => $authorName,
            '{{author_email}}' => $authorEmail,
            '{{extension_key}}' => $extensionKey,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    private function getHeaderAsComment(string $header): string
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();
        $comment = ($this->configuration['comment_type'] === self::HEADER_COMMENT ? '/*' : '/**') . $lineEnding;
        $lines = explode("\n", str_replace("\r", '', $header));

        foreach ($lines as $line) {
            $comment .= rtrim(' * ' . $line) . $lineEnding;
        }

        return $comment . ' */';
    }

    private function findHeaderCommentCurrentIndex(Tokens $tokens, int $headerNewIndex): ?int
    {
        $index = $tokens->getNextNonWhitespace($headerNewIndex);
        if ($index === null || !$tokens[$index]->isComment()) {
            return null;
        }

        $next = $index + 1;
        if (!isset($tokens[$next]) || in_array($this->configuration['separate'], ['top', 'none'], true) || !$tokens[$index]->isGivenKind(T_DOC_COMMENT)) {
            return $index;
        }

        if ($tokens[$next]->isWhitespace()) {
            if (!Preg::match('/^\h*\R\h*$/D', $tokens[$next]->getContent())) {
                return $index;
            }
            ++$next;
        }

        if (!isset($tokens[$next]) || (!$tokens[$next]->isClassy() && !$tokens[$next]->isGivenKind(T_FUNCTION))) {
            return $index;
        }

        return null;
    }

    private function findHeaderCommentInsertionIndex(Tokens $tokens, string $location): int
    {
        $openTagIndex = $tokens[0]->isGivenKind(T_INLINE_HTML) ? 1 : 0;

        if ($location === 'after_open') {
            return $openTagIndex + 1;
        }

        $index = $tokens->getNextMeaningfulToken($openTagIndex);
        if ($index === null) {
            return $openTagIndex + 1;
        }

        if (!$tokens[$index]->isGivenKind(T_DECLARE)) {
            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($index);
        if ($next === null || !$tokens[$next]->equals('(')) {
            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);
        if ($next === null || !$tokens[$next]->equals([T_STRING, 'strict_types'], false)) {
            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);
        if ($next === null || !$tokens[$next]->equals('=')) {
            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);
        if ($next === null || !$tokens[$next]->isGivenKind(T_LNUMBER)) {
            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);
        if ($next === null || !$tokens[$next]->equals(')')) {
            return $openTagIndex + 1;
        }

        $next = $tokens->getNextMeaningfulToken($next);
        if ($next === null || !$tokens[$next]->equals(';')) {
            return $openTagIndex + 1;
        }

        return $next + 1;
    }

    private function fixWhiteSpaceAroundHeader(Tokens $tokens, int $headerIndex): void
    {
        $lineEnding = $this->whitespacesConfig->getLineEnding();

        $expectedLineCount = (in_array($this->configuration['separate'], ['both', 'bottom'], true) && $tokens->getNextMeaningfulToken($headerIndex) !== null)
            ? 2
            : 1;

        if ($headerIndex === count($tokens) - 1) {
            $tokens->insertAt($headerIndex + 1, new Token([T_WHITESPACE, str_repeat($lineEnding, $expectedLineCount)]));
        } else {
            $lineBreakCount = $this->getLineBreakCount($tokens, $headerIndex, 1);
            if ($lineBreakCount < $expectedLineCount) {
                $missing = str_repeat($lineEnding, $expectedLineCount - $lineBreakCount);
                if ($tokens[$headerIndex + 1]->isWhitespace()) {
                    $tokens[$headerIndex + 1] = new Token([T_WHITESPACE, $missing . $tokens[$headerIndex + 1]->getContent()]);
                } else {
                    $tokens->insertAt($headerIndex + 1, new Token([T_WHITESPACE, $missing]));
                }
            } elseif ($lineBreakCount > $expectedLineCount && $tokens[$headerIndex + 1]->isWhitespace()) {
                $newLinesToRemove = $lineBreakCount - $expectedLineCount;
                $tokens[$headerIndex + 1] = new Token([T_WHITESPACE, Preg::replace("/^\R{{$newLinesToRemove}}/", '', $tokens[$headerIndex + 1]->getContent())]);
            }
        }

        $expectedLineCount = in_array($this->configuration['separate'], ['both', 'top'], true) ? 2 : 1;
        $prev = $tokens->getPrevNonWhitespace($headerIndex);
        $regex = '/\h$/';
        if ($tokens[$prev]->isGivenKind(T_OPEN_TAG) && Preg::match($regex, $tokens[$prev]->getContent())) {
            $tokens[$prev] = new Token([T_OPEN_TAG, Preg::replace($regex, $lineEnding, $tokens[$prev]->getContent())]);
        }

        $lineBreakCount = $this->getLineBreakCount($tokens, $headerIndex, -1);
        if ($lineBreakCount < $expectedLineCount) {
            $tokens->insertAt($headerIndex, new Token([T_WHITESPACE, str_repeat($lineEnding, $expectedLineCount - $lineBreakCount)]));
        }
    }

    private function getLineBreakCount(Tokens $tokens, int $index, int $direction): int
    {
        $whitespace = '';

        for ($index += $direction; isset($tokens[$index]); $index += $direction) {
            $token = $tokens[$index];

            if ($token->isWhitespace()) {
                $whitespace .= $token->getContent();
                continue;
            }

            if ($direction === -1 && $token->isGivenKind(T_OPEN_TAG)) {
                $whitespace .= $token->getContent();
            }

            if ($token->getContent() !== '') {
                break;
            }
        }

        return substr_count($whitespace, "\n");
    }

    private function removeHeader(Tokens $tokens, int $index): void
    {
        $prevIndex = $index - 1;
        $prevToken = $tokens[$prevIndex];
        $newlineRemoved = false;

        if ($prevToken->isWhitespace()) {
            $content = $prevToken->getContent();
            if (Preg::match('/\R/', $content)) {
                $newlineRemoved = true;
            }
            $content = Preg::replace('/\R?\h*$/', '', $content);
            $tokens->ensureWhitespaceAtIndex($prevIndex, 0, $content);
        }

        $nextIndex = $index + 1;
        $nextToken = $tokens[$nextIndex] ?? null;

        if (!$newlineRemoved && $nextToken !== null && $nextToken->isWhitespace()) {
            $content = Preg::replace('/^\R/', '', $nextToken->getContent());
            $tokens->ensureWhitespaceAtIndex($nextIndex, 0, $content);
        }

        $tokens->clearTokenAndMergeSurroundingWhitespace($index);
    }

    private function insertHeader(Tokens $tokens, string $headerAsComment, int $index): void
    {
        $tokens->insertAt($index, new Token([
            $this->configuration['comment_type'] === self::HEADER_COMMENT ? T_COMMENT : T_DOC_COMMENT,
            $headerAsComment,
        ]));
        $this->fixWhiteSpaceAroundHeader($tokens, $index);
    }
}

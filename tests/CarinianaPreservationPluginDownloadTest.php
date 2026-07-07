<?php

use PHPUnit\Framework\TestCase;

class CarinianaPreservationPluginDownloadTest extends TestCase
{
    public function testDownloadStatementCaseReturnsBeforeNextCase(): void
    {
        $source = file_get_contents(dirname(__DIR__) . '/CarinianaPreservationPlugin.inc.php');
        $tokens = token_get_all($source);

        $insideDownloadStatementCase = false;
        $foundDownloadStatementCase = false;
        $foundReturnBeforeNextCase = false;

        foreach ($tokens as $index => $token) {
            if (!$insideDownloadStatementCase && $this->isDownloadStatementCase($tokens, $index)) {
                $insideDownloadStatementCase = true;
                $foundDownloadStatementCase = true;
                continue;
            }

            if (!$insideDownloadStatementCase) {
                continue;
            }

            if (is_array($token) && $token[0] === T_RETURN) {
                $foundReturnBeforeNextCase = true;
            }

            if ($index > 0 && is_array($token) && in_array($token[0], [T_CASE, T_DEFAULT], true)) {
                break;
            }
        }

        $this->assertTrue($foundDownloadStatementCase, 'Expected downloadStatement case to exist.');
        $this->assertTrue(
            $foundReturnBeforeNextCase,
            'downloadStatement must return before the next case to avoid mixing file download with JSON upload response.'
        );
    }

    private function isDownloadStatementCase(array $tokens, int $index): bool
    {
        $token = $tokens[$index];
        if (!is_array($token) || $token[0] !== T_CASE) {
            return false;
        }

        $nextIndex = $this->nextMeaningfulTokenIndex($tokens, $index + 1);
        if ($nextIndex === null || !is_array($tokens[$nextIndex])) {
            return false;
        }

        return trim($tokens[$nextIndex][1], '\'"') === 'downloadStatement';
    }

    private function nextMeaningfulTokenIndex(array $tokens, int $index): ?int
    {
        $ignoredTokens = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

        for ($i = $index; $i < count($tokens); $i++) {
            if (is_array($tokens[$i]) && in_array($tokens[$i][0], $ignoredTokens, true)) {
                continue;
            }

            return $i;
        }

        return null;
    }
}

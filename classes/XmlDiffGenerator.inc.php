<?php

class XmlDiffGenerator
{
    public function generate(string $oldContent, string $newContent): ?string
    {
        if ($oldContent === '' || $oldContent === $newContent) {
            return null;
        }

        $oldLines = preg_split("/\r?\n/", rtrim($oldContent));
        $newLines = preg_split("/\r?\n/", rtrim($newContent));

        $matrix = $this->buildLcsMatrix($oldLines, $newLines);
        $diffLines = $this->buildDiffLines($oldLines, $newLines, $matrix);

        return "--- previous\n+++ current\n" . implode("\n", $diffLines) . "\n";
    }

    private function buildLcsMatrix(array $oldLines, array $newLines): array
    {
        $m = count($oldLines);
        $n = count($newLines);
        $lcs = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
        for ($i = $m - 1; $i >= 0; $i--) {
            for ($j = $n - 1; $j >= 0; $j--) {
                $lcs[$i][$j] = ($oldLines[$i] === $newLines[$j])
                    ? $lcs[$i + 1][$j + 1] + 1
                    : max($lcs[$i + 1][$j], $lcs[$i][$j + 1]);
            }
        }
        return $lcs;
    }

    private function buildDiffLines(array $oldLines, array $newLines, array $lcs): array
    {
        $i = 0;
        $j = 0;
        $m = count($oldLines);
        $n = count($newLines);
        $diff = [];
        while ($i < $m && $j < $n) {
            if ($oldLines[$i] === $newLines[$j]) {
                $diff[] = ' ' . $oldLines[$i];
                $i++;
                $j++;
                continue;
            }
            if ($lcs[$i + 1][$j] >= $lcs[$i][$j + 1]) {
                $diff[] = '-' . $oldLines[$i++];
            } else {
                $diff[] = '+' . $newLines[$j++];
            }
        }
        while ($i < $m) {
            $diff[] = '-' . $oldLines[$i++];
        }
        while ($j < $n) {
            $diff[] = '+' . $newLines[$j++];
        }
        return $diff;
    }
}

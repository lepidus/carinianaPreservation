<?php

import('classes.file.PublicFileManager');
import('lib.pkp.classes.file.PrivateFileManager');

class LegacyStatementMigration
{
    private $plugin;

    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

    public function run(): void
    {
        $journalDao = DAORegistry::getDAO('JournalDAO');
        $names = $journalDao->getNames();
        foreach (array_keys($names) as $journalId) {
            $this->migrateStatementFile((int)$journalId);
        }
    }

    private function migrateStatementFile(int $journalId): void
    {
        $publicFileManager = new PublicFileManager();
        $privateFileManager = new PrivateFileManager();
        $publicDir = $publicFileManager->getContextFilesPath($journalId);
        if (!is_dir($publicDir)) {
            return;
        }

        $legacyPublicFiles = glob($publicDir . '/carinianapreservationplugin_responsabilityStatement.*') ?: [];
        $legacyPublicFiles = array_values(array_filter($legacyPublicFiles, 'is_file'));
        if (!$legacyPublicFiles) {
            return;
        }

        $alreadyPreserved = $this->plugin->getSetting($journalId, 'lastPreservationTimestamp');
        $privateDir = $this->getPrivateStatementDir($privateFileManager, $journalId);

        $statementFileSettingJson = $this->plugin->getSetting($journalId, 'statementFile');
        $statementFileSetting = $statementFileSettingJson ? json_decode($statementFileSettingJson, true) : null;
        $hasPrivateFile = $statementFileSetting && !empty($statementFileSetting['fileName']) && is_file($privateDir . '/' . $statementFileSetting['fileName']);

        if ($alreadyPreserved) {
            $this->deleteFiles($legacyPublicFiles);
            if (!$hasPrivateFile) {
                $this->plugin->updateSetting($journalId, 'statementFile', null);
            }
            return;
        }

        if (!$hasPrivateFile) {
            if (!$privateFileManager->fileExists($privateDir, 'dir')) {
                $privateFileManager->mkdirtree($privateDir);
            }
            $source = $legacyPublicFiles[0];
            $ext = pathinfo($source, PATHINFO_EXTENSION) ?: 'pdf';
            $targetName = 'responsabilityStatement.' . $ext;
            $targetPath = $privateDir . '/' . $targetName;
            if (!is_file($targetPath) && @copy($source, $targetPath)) {
                if (is_file($targetPath)) {
                    $privateFileManager->setMode($targetPath, FILE_MODE_MASK);
                    $settingData = json_encode([
                    'originalFileName' => basename($source),
                    'fileName' => $targetName,
                    'fileType' => $this->detectMime($targetPath) ?? 'application/pdf',
                ]);
                    $this->plugin->updateSetting($journalId, 'statementFile', $settingData);
                }
            }
        }

        $this->deleteFiles($legacyPublicFiles);
    }

    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $p) {
            @unlink($p);
        }
    }

    private function getPrivateStatementDir(PrivateFileManager $privateFileManager, int $journalId): string
    {
        $base = rtrim($privateFileManager->getBasePath(), '/');
        return $base . '/carinianaPreservation/' . $journalId;
    }

    private function detectMime(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            if ($f) {
                $mime = finfo_file($f, $path);
                finfo_close($f);
                if ($mime) {
                    return $mime;
                }
            }
        }
        return null;
    }
}

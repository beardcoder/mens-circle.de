<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Upgrades;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('sitepackage_ContentBlocksHeaderUpgradeWizard')]
final class ContentBlocksHeaderUpgradeWizard implements UpgradeWizardInterface
{
    private const TABLE = 'tt_content';
    private const OLD_TYPE = 'menscircle_header';
    private const NEW_TYPE = 'sitepackage_header';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}
    /**
     * Return the speaking name of this wizard
     */
    public function getTitle(): string
    {
        return 'Migrate from Content Blocks Newsletter to traditional';
    }

    /**
     * Return the description for this wizard
     */
    public function getDescription(): string
    {
        return 'this change the tca type';
    }

    public function executeUpdate(): bool
    {
        $this->connectionPool->getConnectionForTable(self::TABLE)
            ->update(
                self::TABLE,
                [ // set
                    'CType' => self::NEW_TYPE,
                ],
                [ // where
                    'CType' => self::OLD_TYPE,
                ],
            );
        return true;
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        try {
            return (bool)$queryBuilder
                ->count('uid')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter(self::OLD_TYPE)),
                )
                ->executeQuery()
                ->fetchOne();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * @return string[]
     */
    public function getPrerequisites(): array
    {
        return [];
    }
}

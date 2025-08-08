<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Upgrades;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('sitepackage_ContentBlocksHeroUpgradeWizard')]
final class ContentBlocksHeroUpgradeWizard implements UpgradeWizardInterface
{
    private const TABLE = 'tt_content';
    private const OLD_TYPE = 'menscircle_hero';
    private const NEW_TYPE = 'sitepackage_hero';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}
    /**
     * Return the speaking name of this wizard
     */
    public function getTitle(): string
    {
        return 'Migrate from Content Blocks Hero to traditional';
    }

    /**
     * Return the description for this wizard
     */
    public function getDescription(): string
    {
        return 'This change the tca type';
    }

    public function executeUpdate(): bool
    {
        $this->connectionPool->getConnectionForTable('tt_content')
            ->update(
                self::TABLE,
                [ // set
                    'CType' => self::NEW_TYPE,
                ],
                [ // where
                    'CType' => self::OLD_TYPE,
                ],
            );
        $this->connectionPool->getConnectionForTable('sys_file_reference')
            ->update(
                'sys_file_reference',
                [ // set
                    'fieldname' => 'assets',
                ],
                [ // where
                    'fieldname' => 'menscircle_hero_image',
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

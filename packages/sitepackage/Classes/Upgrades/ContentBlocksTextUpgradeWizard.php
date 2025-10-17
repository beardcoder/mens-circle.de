<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Upgrades;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('sitepackage_ContentBlocksTextUpgradeWizard')]
final readonly class ContentBlocksTextUpgradeWizard implements UpgradeWizardInterface
{
    private const string TABLE = 'tt_content';

    private const string OLD_TYPE = 'menscircle_text';

    private const string NEW_TYPE = 'sitepackage_text';

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {
    }

    /**
     * Return the speaking name of this wizard.
     */
    public function getTitle(): string
    {
        return 'Migrate from Content Blocks Text to traditional';
    }

    /**
     * Return the description for this wizard.
     */
    public function getDescription(): string
    {
        return 'this change the tca type';
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
            )
        ;

        return true;
    }

    public function updateNecessary(): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        try {
            return (bool) $queryBuilder
                ->count('uid')
                ->from(self::TABLE)
                ->where(
                    $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter(self::OLD_TYPE)),
                )
                ->executeQuery()
                ->fetchOne()
            ;
        } catch (Exception) {
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

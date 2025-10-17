<?php

declare(strict_types=1);

namespace MensCircle\Sitepackage\Upgrades;

use Doctrine\DBAL\Exception;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Install\Attribute\UpgradeWizard;
use TYPO3\CMS\Install\Updates\UpgradeWizardInterface;

#[UpgradeWizard('sitepackage_ContentBlocksNewsletterUpgradeWizard')]
final readonly class ContentBlocksNewsletterUpgradeWizard implements UpgradeWizardInterface
{
    private const string TABLE = 'tt_content';

    private const string OLD_TYPE = 'menscircle_newsletter';

    private const string NEW_TYPE = 'sitepackage_newsletter';

    public function __construct(
        private ConnectionPool $connectionPool,
    ) {
    }

    /**
     * Return the speaking name of this wizard.
     */
    public function getTitle(): string
    {
        return 'Migrate from Content Blocks Newsletter to traditional';
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
        $this->connectionPool->getConnectionForTable(self::TABLE)
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

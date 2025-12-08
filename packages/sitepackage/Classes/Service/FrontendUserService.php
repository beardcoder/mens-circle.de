<?php

declare(strict_types=1);

/*
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Service;

use MensCircle\Sitepackage\Domain\Model\FrontendUser;
use MensCircle\Sitepackage\Domain\Model\Newsletter\Subscription;
use MensCircle\Sitepackage\Domain\Model\Participant;
use MensCircle\Sitepackage\Domain\Repository\FrontendUserRepository;
use Nette\Utils\Random;
use TYPO3\CMS\Core\Crypto\PasswordHashing\InvalidPasswordHashException;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\IllegalObjectTypeException;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

readonly class FrontendUserService
{
    public function __construct(
        private FrontendUserRepository $frontendUserRepository,
        private PersistenceManager $persistenceManager,
        private PasswordHashFactory $passwordHashFactory,
    ) {
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidPasswordHashException
     */
    public function mapToFrontendUser(Subscription|Participant $model): FrontendUser
    {
        $frontendUser = $this->frontendUserRepository->findOneBy([
            'email' => $model->email,
        ]);

        if (!$frontendUser instanceof FrontendUser) {
            return $this->mapDataToFrontendUser($model);
        }

        return $frontendUser;
    }

    /**
     * @throws IllegalObjectTypeException
     * @throws InvalidPasswordHashException
     */
    private function mapDataToFrontendUser(Subscription|Participant $data): FrontendUser
    {
        /** @var FrontendUser $frontendUser */
        $frontendUser = GeneralUtility::makeInstance(FrontendUser::class);

        $frontendUser->email = $data->email;
        $frontendUser->firstName = $data->firstName;
        $frontendUser->lastName = $data->lastName;
        $frontendUser->username = $data->email;

        $passwordHash = $this->passwordHashFactory->getDefaultHashInstance('FE');
        $frontendUser->password = $passwordHash->getHashedPassword(Random::generate());

        $this->frontendUserRepository->add($frontendUser);
        $this->persistenceManager->persistAll();

        return $frontendUser;
    }
}

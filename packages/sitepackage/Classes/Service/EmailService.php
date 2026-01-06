<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Service;

use MensCircle\Sitepackage\Components\ComponentCollection;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\View\TemplatePaths;

final readonly class EmailService
{
    private const DEFAULT_TEMPLATE_ROOT = 'EXT:sitepackage/Resources/Private/Templates/Email/';
    private const DEFAULT_PARTIAL_ROOT = 'EXT:sitepackage/Resources/Private/Components/Email/';
    private const DEFAULT_LAYOUT_ROOT = 'EXT:sitepackage/Resources/Private/Layouts/Email/';

    public function __construct(
        private MailerInterface $mailer,
        private SiteFinder $siteFinder,
        private LoggerInterface $logger,
    ) {}

    /**
     * @param array<string, mixed> $variables
     */
    public function send(
        string $template,
        string $subject,
        string|Address $to,
        array $variables = [],
        ?string $from = null,
        ?string $replyTo = null,
    ): bool {
        try {
            $email = $this->createEmail($template, $subject, $variables);
            $email->to($to instanceof Address ? $to : new Address($to));

            if ($from !== null) {
                $email->from(new Address($from));
            }

            if ($replyTo !== null) {
                $email->replyTo(new Address($replyTo));
            }

            $this->mailer->send($email);

            $this->logger->info('Email sent successfully', [
                'template' => $template,
                'to' => (string)$to,
                'subject' => $subject,
            ]);

            return true;
        } catch (\Throwable $e) {
            $this->logger->error('Failed to send email', [
                'template' => $template,
                'to' => (string)$to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function sendToMultiple(
        string $template,
        string $subject,
        array $recipients,
        array $variables = [],
    ): int {
        $sent = 0;

        foreach ($recipients as $recipient) {
            if ($this->send($template, $subject, $recipient, $variables)) {
                $sent++;
            }
        }

        return $sent;
    }

    /**
     * @param array<string, mixed> $variables
     */
    private function createEmail(string $template, string $subject, array $variables): FluidEmail
    {
        $email = GeneralUtility::makeInstance(FluidEmail::class);

        $email
            ->setTemplate($template)
            ->subject($subject)
            ->assignMultiple($this->getDefaultVariables())
            ->assignMultiple($variables);

        $this->configureTemplatePaths($email);
        $this->registerComponentNamespace($email);

        return $email;
    }

    private function configureTemplatePaths(FluidEmail $email): void
    {
        $templatePaths = new TemplatePaths();

        $templatePaths->setTemplateRootPaths([
            10 => GeneralUtility::getFileAbsFileName('EXT:core/Resources/Private/Templates/Email/'),
            20 => GeneralUtility::getFileAbsFileName(self::DEFAULT_TEMPLATE_ROOT),
        ]);

        $templatePaths->setPartialRootPaths([
            10 => GeneralUtility::getFileAbsFileName('EXT:core/Resources/Private/Partials/'),
            20 => GeneralUtility::getFileAbsFileName(self::DEFAULT_PARTIAL_ROOT),
        ]);

        $templatePaths->setLayoutRootPaths([
            10 => GeneralUtility::getFileAbsFileName('EXT:core/Resources/Private/Layouts/'),
            20 => GeneralUtility::getFileAbsFileName(self::DEFAULT_LAYOUT_ROOT),
        ]);

        $email->setTemplatePaths($templatePaths);
    }

    private function registerComponentNamespace(FluidEmail $email): void
    {
        $renderingContext = $email->getRenderingContext();

        $renderingContext->getViewHelperResolver()->addNamespace(
            'mc',
            ComponentCollection::class,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function getDefaultVariables(): array
    {
        $site = $this->getCurrentSite();

        return [
            'settings' => [
                'baseUrl' => $site?->getBase()->__toString() ?? 'https://mens-circle.de',
                'siteName' => 'Mens Circle',
            ],
        ];
    }

    private function getCurrentSite(): ?\TYPO3\CMS\Core\Site\Entity\Site
    {
        try {
            $sites = $this->siteFinder->getAllSites();

            return $sites['main'] ?? reset($sites) ?: null;
        } catch (\Throwable) {
            return null;
        }
    }
}

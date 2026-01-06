<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Domain\Model;

use MensCircle\Sitepackage\Enum\NewsletterStatus;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Newsletter extends AbstractEntity
{
    protected string $subject = '';

    protected string $preheader = '';

    protected string $content = '';

    protected NewsletterStatus $status = NewsletterStatus::Draft;

    protected ?\DateTimeImmutable $scheduledAt = null;

    protected ?\DateTimeImmutable $sentAt = null;

    protected int $recipientsCount = 0;

    protected int $sentCount = 0;

    protected int $failedCount = 0;

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getPreheader(): string
    {
        return $this->preheader;
    }

    public function setPreheader(string $preheader): self
    {
        $this->preheader = $preheader;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getStatus(): NewsletterStatus
    {
        return $this->status;
    }

    public function setStatus(NewsletterStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): self
    {
        $this->sentAt = $sentAt;

        return $this;
    }

    public function getRecipientsCount(): int
    {
        return $this->recipientsCount;
    }

    public function setRecipientsCount(int $recipientsCount): self
    {
        $this->recipientsCount = $recipientsCount;

        return $this;
    }

    public function getSentCount(): int
    {
        return $this->sentCount;
    }

    public function setSentCount(int $sentCount): self
    {
        $this->sentCount = $sentCount;

        return $this;
    }

    public function incrementSentCount(): self
    {
        $this->sentCount++;

        return $this;
    }

    public function getFailedCount(): int
    {
        return $this->failedCount;
    }

    public function setFailedCount(int $failedCount): self
    {
        $this->failedCount = $failedCount;

        return $this;
    }

    public function incrementFailedCount(): self
    {
        $this->failedCount++;

        return $this;
    }

    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    public function canSend(): bool
    {
        return $this->status->canSend();
    }

    public function markAsSending(int $recipientsCount): self
    {
        $this->status = NewsletterStatus::Sending;
        $this->recipientsCount = $recipientsCount;
        $this->sentCount = 0;
        $this->failedCount = 0;

        return $this;
    }

    public function markAsSent(): self
    {
        $this->status = NewsletterStatus::Sent;
        $this->sentAt = new \DateTimeImmutable();

        return $this;
    }

    public function markAsFailed(): self
    {
        $this->status = NewsletterStatus::Failed;

        return $this;
    }
}

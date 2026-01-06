<?php

declare(strict_types=1);

/*
 * This file is part of the "sitepackage" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace MensCircle\Sitepackage\Domain\Model;

use MensCircle\Sitepackage\Enum\SubscriberStatus;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Subscriber extends AbstractEntity
{
    protected string $email = '';

    protected SubscriberStatus $status = SubscriberStatus::Pending;

    protected string $token = '';

    protected ?\DateTimeImmutable $confirmedAt = null;

    protected ?\DateTimeImmutable $unsubscribedAt = null;

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getStatus(): SubscriberStatus
    {
        return $this->status;
    }

    public function setStatus(SubscriberStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function getConfirmedAt(): ?\DateTimeImmutable
    {
        return $this->confirmedAt;
    }

    public function setConfirmedAt(?\DateTimeImmutable $confirmedAt): self
    {
        $this->confirmedAt = $confirmedAt;

        return $this;
    }

    public function getUnsubscribedAt(): ?\DateTimeImmutable
    {
        return $this->unsubscribedAt;
    }

    public function setUnsubscribedAt(?\DateTimeImmutable $unsubscribedAt): self
    {
        $this->unsubscribedAt = $unsubscribedAt;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function confirm(): self
    {
        $this->status = SubscriberStatus::Confirmed;
        $this->confirmedAt = new \DateTimeImmutable();
        $this->token = '';

        return $this;
    }

    public function unsubscribe(): self
    {
        $this->status = SubscriberStatus::Unsubscribed;
        $this->unsubscribedAt = new \DateTimeImmutable();

        return $this;
    }
}

<?php

declare(strict_types=1);

/*
 * This file is part of the mens-circle/sitepackage extension.
 * Created by Markus Sommer
 * "Slow your breath, slow your mind — let the right code appear."
 */

namespace MensCircle\Sitepackage\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class FrontendUser extends AbstractEntity
{
    public string $firstName = '' {
        get => $this->firstName;
        set {
            $this->firstName = $value;
        }
    }

    public string $lastName = '' {
        get => $this->lastName;
        set {
            $this->lastName = $value;
        }
    }

    public string $email = '' {
        get => $this->email;
        set {
            $this->email = $value;
        }
    }

    public string $username = '' {
        get => $this->username;
        set {
            $this->username = $value;
        }
    }

    public string $password = '' {
        get => $this->password;
        set {
            $this->password = $value;
        }
    }

    /**
     * Computed property: Full name from first and last name.
     * Read-only - setting name parses it into first/last name.
     */
    public string $name {
        get => trim("{$this->firstName} {$this->lastName}");

        set {
            $this->name = trim("{$this->firstName} {$this->lastName}");
        }
    }
}

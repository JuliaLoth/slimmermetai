<?php

declare(strict_types=1);

namespace App\Infrastructure\Repository;

use App\Domain\Entity\StripeSession;

/**
 * Infrastructure StripeSessionRepositoryInterface for test compatibility
 */
interface StripeSessionRepositoryInterface extends \App\Domain\Repository\StripeSessionRepositoryInterface
{
    // All methods are inherited from the domain interface
}

<?php

declare(strict_types=1);

namespace App\Model;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use TheCodency\TheCms\Core\Shared\Domain\AggregateRoot;

class Flusher
{
    private $em;
    private $dispatcher;

    public function __construct(EntityManagerInterface $em/*, EventDispatcher $dispatcher*/)
    {
        $this->em = $em;
    }

    public function flush(AggregateRoot ...$roots): void
    {
        $this->em->flush();

        foreach ($roots as $root) {
            $this->dispatcher->dispatch($root->releaseEvents());
        }
    }
}
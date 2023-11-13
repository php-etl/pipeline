<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline;

use Kiboko\Contract\Pipeline\StepCodeInterface;

final class StepCode implements StepCodeInterface
{
    private function __construct(
        private readonly string $reference,
    ) {
    }

    public static function fromString(string $reference): self
    {
        return new self($reference);
    }

    public function __toString(): string
    {
        return $this->reference;
    }
}

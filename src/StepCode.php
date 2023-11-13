<?php

declare(strict_types=1);

namespace Kiboko\Component\Pipeline;

use Kiboko\Contract\Pipeline\StepCodeInterface;

final readonly class StepCode implements StepCodeInterface
{
    private function __construct(
        private string $reference,
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

<?php

namespace WebmanTech\DTO\Integrations;

interface ValidatorStopOnFirstFailureInterface
{
    public function stopOnFirstFailure(bool $enable = true): static;
}

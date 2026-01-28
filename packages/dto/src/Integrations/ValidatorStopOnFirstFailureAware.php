<?php

namespace WebmanTech\DTO\Integrations;

trait ValidatorStopOnFirstFailureAware
{
    protected bool $stopOnFirstFailure = false;

    public function stopOnFirstFailure(bool $stopOnFirstFailure = true): static
    {
        $self = clone $this;
        $self->stopOnFirstFailure = $stopOnFirstFailure;
        return $self;
    }
}

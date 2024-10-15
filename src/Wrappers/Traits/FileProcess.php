<?php

declare(strict_types=1);

namespace Vaskiq\LaravelFileLayer\Wrappers\Traits;

trait FileProcess
{
    protected string $workingPath;

    use FileActions;
    use FileInfo;

    public function getWorkingPath(): string
    {
        if (! isset($this->workingPath)) {
            if (! $this->isLocal()) {
                $this->workingPath = $this->getStorageManager()->getWorkingCopy();
            }
            $this->workingPath = $this->getStorageManager()->fullPath($this);
        }

        return $this->workingPath;
    }

    public function commit(): self
    {
        if ($this->isLocal()) {
            return $this;
        }

        $this->getStorageManager()->commit($this);

        return $this;
    }
}

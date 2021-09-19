<?php

namespace Amp\Sync;

use Amp\Deferred;
use function Amp\await;

class LocalMutex implements Mutex
{
    private bool $locked = false;

    /** @var Deferred[] */
    private array $queue = [];

    /** {@inheritdoc} */
    public function acquire(): Lock
    {
        if (!$this->locked) {
            $this->locked = true;
            return new Lock(0, \Closure::fromCallable([$this, 'release']));
        }

        $this->queue[] = $deferred = new Deferred;
        return $deferred->getFuture()->await();
    }

    private function release(): void
    {
        if (!empty($this->queue)) {
            $deferred = \array_shift($this->queue);
            $deferred->complete(new Lock(0, \Closure::fromCallable([$this, 'release'])));
            return;
        }

        $this->locked = false;
    }
}

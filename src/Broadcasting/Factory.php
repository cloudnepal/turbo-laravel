<?php

namespace Tonysm\TurboLaravel\Broadcasting;

use Illuminate\Broadcasting\Channel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Assert;
use Tonysm\TurboLaravel\Models\Naming\Name;

class Factory
{
    protected $recording = false;
    protected $recordedStreams = [];

    public function fake()
    {
        $this->recording = true;

        return $this;
    }

    public function broadcastAppend($content = null, Model|string|null $target = null, ?string $targets = null, Channel|Model|Collection|array|string|null $channel = null)
    {
        return $this->broadcastAction('append', $content, $target, $targets, $channel);
    }

    public function broadcastPrepend($content = null, Model|string|null $target = null, ?string $targets = null, Channel|Model|Collection|array|string|null $channel = null)
    {
        return $this->broadcastAction('prepend', $content, $target, $targets, $channel);
    }

    public function broadcastBefore($content = null, Model|string|null $target = null, ?string $targets = null, Channel|Model|Collection|array|string|null $channel = null)
    {
        return $this->broadcastAction('before', $content, $target, $targets, $channel);
    }

    public function broadcastAfter($content = null, Model|string|null $target = null, ?string $targets = null, Channel|Model|Collection|array|string|null $channel = null)
    {
        return $this->broadcastAction('after', $content, $target, $targets, $channel);
    }

    public function broadcastUpdate($content = null, Model|string|null $target = null, ?string $targets = null, Channel|Model|Collection|array|string|null $channel = null)
    {
        return $this->broadcastAction('update', $content, $target, $targets, $channel);
    }

    public function broadcastReplace($content = null, Model|string|null $target = null, ?string $targets = null, Channel|Model|Collection|array|string|null $channel = null)
    {
        return $this->broadcastAction('replace', $content, $target, $targets, $channel);
    }

    public function broadcastRemove(Model|string|null $target = null, ?string $targets = null, Channel|Model|Collection|array|string|null $channel = null)
    {
        return $this->broadcastAction('remove', null, $target, $targets, $channel);
    }

    public function broadcastAction(string $action, $content = null, Model|string|null $target = null, ?string $targets = null, Channel|Model|Collection|array|string|null $channel = null)
    {
        $broadcast = new PendingBroadcast(
            channels: $channel ? $this->resolveChannels($channel) : [],
            action: $action,
            target: $target instanceof Model ? $this->resolveTargetFor($target, resource: true) : $target,
            targets: $targets,
            rendering: $this->resolveRendering($content),
        );

        if ($this->recording) {
            $broadcast->fake($this);
        }

        return $broadcast;
    }

    public function record(PendingBroadcast $broadcast)
    {
        $this->recordedStreams[] = $broadcast;

        return $this;
    }

    protected function resolveRendering($content)
    {
        if ($content instanceof Rendering) {
            return $content;
        }

        return $content ? Rendering::forContent($content) : Rendering::empty();
    }

    protected function resolveChannels(Channel|Model|Collection|array|string $channel)
    {
        if (is_array($channel) || $channel instanceof Collection) {
            return collect($channel)->flatMap(function ($channel) {
                return $this->resolveChannels($channel);
            })->values()->filter()->all();
        }

        if (is_string($channel)) {
            return [new Channel($channel)];
        }

        if ($channel instanceof Model) {
            return $channel->asTurboStreamBroadcastingChannel();
        }

        return [$channel];
    }

    protected function resolveTargetFor(Model|string $target, bool $resource = false): string
    {
        if (is_string($target)) {
            return $target;
        }

        if ($resource) {
            return $this->getResourceNameFor($target);
        }

        return dom_id($target);
    }

    protected function getResourceNameFor(Model $model): string
    {
        return Name::forModel($model)->plural;
    }

    public function assertBroadcasted($callback)
    {
        $result = collect($this->recordedStreams)->filter($callback);

        Assert::assertGreaterThanOrEqual(1, $result->count(), 'Expected to have broadcasted Turbo Streams, but it did not.');

        return $this;
    }

    public function assertBroadcastedTimes($callback, $times = 1, $message = null)
    {
        $result = collect($this->recordedStreams)->filter($callback);

        Assert::assertCount($times, $result, $message ?: sprintf('Expected to have broadcasted %s, but broadcasted %d instead.', trans_choice('{0} nothing|{1}a Turbo Stream|[2,*]:value Turbo Streams', $result->count()), $result->count()));

        return $this;
    }

    public function assertNothingWasBroadcasted()
    {
        return $this->assertBroadcastedTimes(function () {
            return true;
        }, 0, sprintf('Expected to not have broadcasted any Turbo Stream, but broadcasted %d instead.', count($this->recordedStreams)));
    }
}

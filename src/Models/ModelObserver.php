<?php

namespace HotwiredLaravel\TurboLaravel\Models;

use Illuminate\Database\Eloquent\Model;

class ModelObserver
{
    /**
     * Only dispatch the observer's events after all database transactions have committed.
     *
     * @var bool
     */
    public $afterCommit;

    public function __construct()
    {
        $this->afterCommit = config('turbo-laravel.queue');
    }

    /**
     * @param  Model|Broadcasts  $model
     */
    public function saved(Model $model)
    {
        if (! $this->shouldBroadcast($model)) {
            return;
        }

        if ($model->wasRecentlyCreated) {
            $model->broadcastInsert()->later();
        } else {
            $model->broadcastReplace()->later();
        }
    }

    /**
     * @param  Model|Broadcasts  $model
     */
    public function deleted(Model $model)
    {
        if (! $this->shouldBroadcast($model)) {
            return;
        }

        $model->broadcastRemove()->later();
    }

    private function shouldBroadcast(Model $model): bool
    {
        if (property_exists($model, 'broadcasts')) {
            return true;
        }

        if (property_exists($model, 'broadcastsTo')) {
            return true;
        }

        return method_exists($model, 'broadcastsTo');
    }
}

<?php

namespace App\Observers;

use A17\Twill\Models\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\ResponseCache\Facades\ResponseCache;

class CacheObserver
{
  /**
   * Handle the Model "created" event.
   */
  public function created(Model $model): void
  {
    Cache::flush();
    ResponseCache::clear();
  }

  /**
   * Handle the Model "updated" event.
   */
  public function updated(Model $model): void
  {
    Cache::flush();
    ResponseCache::clear();
  }

  /**
   * Handle the Model "deleted" event.
   */
  public function deleted(Model $model): void
  {
    Cache::flush();
    ResponseCache::clear();
  }

  /**
   * Handle the Model "restored" event.
   */
  public function restored(Model $model): void
  {
    Cache::flush();
    ResponseCache::clear();
  }

  /**
   * Handle the Model "force deleted" event.
   */
  public function forceDeleted(Model $model): void
  {
    Cache::flush();
    ResponseCache::clear();
  }
}

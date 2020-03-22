<?php

/**
 * @ Author: Jone Pólvora
 * @ Create Time: 2020-03-22 15:51:13
 * @ Description:
 * @ Modified by: Jone Pólvora
 * @ Modified time: 2020-03-22 16:11:59
 */

namespace jpolvora;

use Error;
use Exception;

class Locker
{
  private $executed = false;
  private $released = false;
  private string $filename = '';
  private $lock = null;

  private function __construct(string $key = '')
  {
    if (empty($key)) throw new Exception('$key');

    $this->key = $key;
    $this->filename = __DIR__  . DIRECTORY_SEPARATOR . "lock_$key";
  }

  public static function ExecuteLock(string $key = '', callable $fn, int $retries = 3, int $sleep = 300000)
  {
    $result = null;

    $locker = new Locker($key);

    while (true) {
      if ($retries === 0) {
        $result = null;
        break;
      }

      if ($locker->acquireLock()) {
        $result = $locker->execute($fn);
        break;
      } else {
        $retries--;
        usleep($sleep); //100ms
      }
    }

    $locker->release();

    return $result;
  }

  public function acquireLock()
  {
    if ($this->released) throw new Error('already released');
    if ($this->executed) throw new Error('already executed');
    if ($this->isAcquired()) throw new Error('already acquired');

    $filename = $this->filename;

    if (is_file($filename)) return false;

    $fp = fopen($filename, "w");
    if (flock($fp, LOCK_EX)) {  // acquire an exclusive lock
      $this->lock = $fp;
      return true;
    } else {
      $this->lock = null;
      return false;
    }
  }

  private function isAcquired()
  {
    return $this->lock && is_resource($this->lock);
  }

  public function execute(callable $fn)
  {
    if ($this->released) throw new Error('already released');
    if ($this->executed) throw new Error('already executed');
    if (!$this->isAcquired()) throw new Error('not acquired!');

    $result = null;
    try {

      $result = $fn();
    } catch (\Throwable $th) {
      $result = $th;
    } finally {
      $this->executed = true;
    }

    return $result;
  }

  public function release()
  {
    if ($this->released) throw new Error('already released');

    if ($this->lock) {
      flock($this->lock, LOCK_UN);    // release5 the lock
      fclose($this->lock);
    }
    unlink($this->filename);
    $this->lock = null;
    $this->released = true;
  }
}

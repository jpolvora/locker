<?php

use jpolvora\Locker;

require_once('../src/Locker.php');

function run()
{
  return 'ok';
}

$key = 'abc';
$filename = __DIR__  . DIRECTORY_SEPARATOR . "src/lock_$key";
if (is_file($filename)) unlink($filename);

$result = Locker::ExecuteLock($key, function () {
  return run();
}, 3, 400000);

echo $result;

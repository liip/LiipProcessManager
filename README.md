ProcessManager
==============

Provides a simple locking mechanism based on UNIX process id's written to a "PID file".

[![Build Status](https://secure.travis-ci.org/liip/LiipProcessManager.png)](http://travis-ci.org/liip/LiipProcessManager)

http://github.com/liip/LiipProcessManager.git

Here is a simple example

    <?php
    use Liip\ProcessManager\ProcessManager;
    use Liip\ProcessManager\PidFile;

    // run a process in the back ground
    $processManager = new ProcessManager();
    $pid = $processManager->execProcess('sleep 10m');
    $processManager->isProcessRunning($pid)
    $processManager->killProcess($pid);

    // to set log location instead of routing it to /dev/null by default
    $processManager = new ProcessManager('/path/to/logfile');
    $pid = $processManager->execProcess('sleep 10m');

    // acquire a lock via a pid file
    $lock = new PidFile(new ProcessManager(), '/tmp/foobar');
    $lock->acquireLock();
    $pid = $lock->execProcess('sleep 10m');
    // set the PID which should be locked on
    $lock->setPid(getmypid());
    $lock->releaseLock();

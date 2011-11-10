ProcessManager
==============

Provides a simple locking mechanism based on UNIX process id's written to a "PID file".

Here is a simple example

    use Liip\ProcessManager\ProcessManager;

    // run a process in the back ground
    $processManager = new ProcessManager();
    $pid = $processManager->execProcess('sleep 10m');
    $processManager->isProcessRunning($pid)
    $processManager->killProcess($pid);

    // acquire a lock via a pid file
    $lock = new PidFile(new ProcessManager(), '/tmp/foobar');
    $lock->acquireLock();
    $pid = $lock->execProcess('sleep 10m');
    // set the PID which should be locked on
    $lock->setPid(getmypid());
    $lock->releaseLock();

<?php

namespace Liip\ProcessManager;

/**
 * Basic unix process management
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 */
class ProcessManager
{

    /**
     * Output log location
     *
     * @var string
     */
    protected $log = '/dev/null';

    /**
     * Sets up a new ProcessManager
     *
     * @param string $log Location to output log file
     */
    public function __construct($log = '/dev/null')
    {
        $this->log = $log;
    }

    /**
     * Exec a command in the background and return the PID
     *
     * @param string $command
     *
     * @return int PID
     */
    public function execProcess($command)
    {
        // The double & in the command "(.... &)&" will have the same effect as nohup
        // but the pid returned is actually the correct pid (which is not the case when
        // using nohup).
        $command = '(' . $command.' > ' . $this->log . ' 2>&1 & echo $!)&';
        exec($command, $op);
        return (int)$op[0];
    }

    /**
     * Check if the PID is a running process.
     *
     * @param string $pid The PID to write to the file
     *
     * @return boolean
     */
    public function isProcessRunning($pid)
    {
        // Warning: this will only work on Unix
        return ($pid !== '') && file_exists("/proc/$pid");
    }

    /**
     * Kill the currently running process
     *
     * @param string $pid The PID to write to the file
     *
     * @return boolean
     */
    public function killProcess($pid)
    {
        return posix_kill($pid, SIGKILL);
    }
}

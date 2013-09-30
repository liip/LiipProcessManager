<?php

namespace Liip\ProcessManager;

/**
 * Basic file based locking mechanism
 *
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 */
class PidFile
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var ProcessManager
     */
    protected $processManager;

    /**
     * @var resource
     */
    protected $file = null;

    /**
     * Prepares new lock on the file $filename
     * 
     * @param ProcessManager $processManager Process manager instance
     * @param string $filename The name of the lock file
     */
    public function __construct(ProcessManager $processManager, $filename)
    {
        $this->processManager = $processManager;
        $this->filename = $filename;
    }

    /**
     * Acquire a lock on the lock file.
     */
    public function acquireLock()
    {
        $dir = dirname($this->filename);
        if (!file_exists($dir)) {
            mkdir($dir, 0775, true);
        }

        $this->file = fopen($this->filename, 'a+');
        if (! flock($this->file, LOCK_EX  | LOCK_NB)) {
            throw new \Exception('Could not lock the pidfile');
        }
    }

    /**
     * Write the given PID to the lock file. The file must be locked before!
     *
     * @param string $pid The PID to write to the file
     */
    public function setPid($pid)
    {
        if (null === $this->file) {
            throw new \Exception('The pidfile is not locked');
        }

        ftruncate($this->file, 0);
        fwrite($this->file, $pid);
    }

    /**
     * Read the PID written in the lock file. The file must be locked before!
     *
     * @return string
     */
    public function getPid()
    {
        if (null === $this->file) {
            throw new \Exception('The pidfile is not locked');
        }

        return file_get_contents($this->filename);
    }

    /**
     * Exec a command in the background and return the PID
     * 
     * @return string
     */
    public function execProcess($command)
    {
        return $this->processManager->execProcess($command);
    }

    /**
     * Check if the PID written in the lock file corresponds to a running process.
     * The file must be locked before!
     * @return boolean
     */
    public function isProcessRunning()
    {
        if (null === $this->file) {
            throw new \Exception('The pidfile is not locked');
        }

        $pid = $this->getPid();

        return $this->processManager->isProcessRunning($pid);
    }

    /**
     * Kill the currently running process
     */
    public function killProcess()
    {
        return $this->processManager->killProcess($this->getPid());
    }

    /**
     * Release the lock on the lock file
     */
    public function releaseLock()
    {
        if (! is_resource($this->file)) {
		    return false;
	    }

        flock($this->file, LOCK_UN);
        fclose($this->file);
        @unlink($this->file);
        $this->file = null;
		return true;
    }
}

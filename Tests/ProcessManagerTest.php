<?php

namespace Nzz\ImportBundle\Tests\Helper;

use Liip\ProcessManager\PidFile;
use Liip\ProcessManager\ProcessManager;

/**
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 */
class ProcessManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testExecProcess()
    {
        $processManager = new ProcessManager();

        $child_pid = $processManager->execProcess('sleep 10m');

        // Assert the 'sleep 10m' command is running
        $out = null;
        $process_running = false;
        exec('ps aux', $out);
        foreach ($out as $row) {
            if (strpos($row, 'sleep 10m') !== false) {
                $process_running = true;
                break;
            }
        }

        $this->assertTrue($process_running, "The process $child_pid is not running");

        // Kill the sleeping process
        exec("kill -9 $child_pid");
    }

    public function testIsProcessRunning()
    {
        $processManager = new ProcessManager();

        if (!is_dir('/proc')) {
            $this->markTestSkipped('Mac does not have the proc dir.');
        }
        $this->assertTrue($processManager->isProcessRunning(getmypid()), "ProcessManager reported that my process is not running");

        $this->assertFalse($processManager->isProcessRunning('Unexisting_pid'), "ProcessManager reported that an unexisting pid process is running");

        $this->assertFalse($processManager->isProcessRunning(''), "ProcessManager reported that an empty pid process is running");
    }

    public function testKillProcess()
    {
        $processManager = new ProcessManager();

        $child_pid = $processManager->execProcess('sleep 10m');

        // Let the time to the process to be spawned, otherwise we can have
        // cases where the ProcessManager tries to kill a process which is
        // actually not yet started --> test failing
        sleep(1);

        $process_killed = $processManager->killProcess($child_pid);

        // Assert the process was killed
        $this->assertTrue($process_killed);

        // Assert the 'sleep 10m' command is not running anymore
        $out = null;
        exec('ps aux', $out);
        foreach ($out as $row) {
            if (strpos($row, 'sleep 10m') !== false) {
                exec("kill -9 $child_pid");
                $this->fail("The process $child_pid is supposed to have been killed, yet it was still running. Had to kill it in cleanup !");
            }
        }
    }

}


<?php

namespace Nzz\ImportBundle\Tests\Helper;

use Liip\ProcessManager\LockException;
use Liip\ProcessManager\PidFile;
use Liip\ProcessManager\ProcessManager;

/**
 * @author Lukas Kahwe Smith <smith@pooteeweet.org>
 * @author Daniel Barsotti <daniel.barsotti@liip.ch>
 */
class PidFileTest extends \PHPUnit_Framework_TestCase
{
    protected $processManager;
    protected $pidfile = '/tmp/nzzApiTestPidFile';

    public function setUp()
    {
        $this->processManager = $this->getMockBuilder('Liip\ProcessManager\ProcessManager')
            ->disableOriginalConstructor()->getMock();
    }

    public function tearDown()
    {
        if (file_exists($this->pidfile)) {
            unlink($this->pidfile);
        }
    }

    /**
     * @covers Liip\ProcessManager\PidFile::__construct
     */
    public function testConstructor()
    {
        $helper = new PidFile($this->processManager, $this->pidfile);
        $this->assertAttributeSame($this->pidfile, 'filename', $helper);
        $this->assertAttributeSame(null, 'file', $helper);
    }

    /**
     * @covers Liip\ProcessManager\PidFile::acquireLock
     * @covers Liip\ProcessManager\PidFile::releaseLock
     */
    public function testLockingUnlocking()
    {
        $helper = new PidFile($this->processManager, $this->pidfile);
        $helper->acquireLock();

        $this->assertAttributeNotSame(null, 'file', $helper);
        $this->assertTrue(file_exists($this->pidfile));

        $helper->releaseLock();

        $this->assertAttributeSame(null, 'file', $helper);
    }

    /**
     * @covers Liip\ProcessManager\PidFile::acquireLock
     * @covers Liip\ProcessManager\PidFile::releaseLock
     */
    public function testLockingLockedFileDoesNotWork()
    {
        $helper1 = new PidFile($this->processManager, $this->pidfile);
        $helper2 = new PidFile($this->processManager, $this->pidfile);

        $helper1->acquireLock();

        try {
            $helper2->acquireLock();
        } catch (LockException $ex) {
            $this->assertEquals('Could not lock the pidfile', $ex->getMessage());
        }

        $helper1->releaseLock();
    }

    /**
     * @covers Liip\ProcessManager\PidFile::acquireLock
     * @covers Liip\ProcessManager\PidFile::releaseLock
     */
    public function testLockingUnlockedFileWorks()
    {
        $helper1 = new PidFile($this->processManager, $this->pidfile);
        $helper2 = new PidFile($this->processManager, $this->pidfile);

        $helper1->acquireLock();
        $helper1->releaseLock();

        $helper2->acquireLock();
        $helper2->releaseLock();
    }

    /**
     * @covers Liip\ProcessManager\PidFile::setPid
     * @covers Liip\ProcessManager\PidFile::getPid
     */
    public function testGetSetPid()
    {
        $helper = new PidFile($this->processManager, $this->pidfile);
        $helper->acquireLock();
        $helper->setPid('my_first_pid');
        $helper->setPid('my_pid');

        $this->assertEquals('my_pid', file_get_contents($this->pidfile));

        $pid = $helper->getPid();
        $this->assertEquals('my_pid', $pid);

        $helper->releaseLock();
    }

    /**
     * @covers Liip\ProcessManager\PidFile::isProcessRunning
     */
    public function testIsProcessRunning()
    {
        $processManager = $this->getMockBuilder('Liip\ProcessManager\ProcessManager')->disableOriginalConstructor()->getMock();
        $processManager->expects($this->any())
            ->method('isProcessRunning')
            ->will($this->onConsecutiveCalls(true, false, false))
        ;

        $helper = new PidFile($processManager, $this->pidfile);
        $helper->acquireLock();

        // Test with my real pid
        $helper->setPid('foo');
        $this->assertTrue($helper->isProcessRunning());

        // Test with a fake pid
        $helper->setPid('1234a');// letter, to make sure, process does really not exist
        $this->assertFalse($helper->isProcessRunning());

        // Test with empty pid
        $helper->setPid('');
        $this->assertFalse($helper->isProcessRunning());

        $helper->releaseLock();
    }

    /**
     * @covers Liip\ProcessManager\PidFile::getPid
     * @covers Liip\ProcessManager\PidFile::setPid
     * @covers Liip\ProcessManager\PidFile::isProcessRunning
     */
    public function testCannotDoAnythingIfNotLocked()
    {
        $helper = new PidFile($this->processManager, $this->pidfile);

        try {
            $helper->getPid();
        } catch (LockException $ex) {
            $this->assertEquals('The pidfile is not locked', $ex->getMessage());
        }

        try {
            $helper->setPid('1234');
        } catch (LockException $ex) {
            $this->assertEquals('The pidfile is not locked', $ex->getMessage());
        }

        try {
            $helper->isProcessRunning();
        } catch (LockException $ex) {
            $this->assertEquals('The pidfile is not locked', $ex->getMessage());
        }
    }

    /**
     * @covers Liip\ProcessManager\PidFile::execProcess
     */
    public function testExecProcess()
    {
        $processManager = $this->getMockBuilder('Liip\ProcessManager\ProcessManager')->disableOriginalConstructor()->getMock();
        $processManager->expects($this->once())
            ->method('execProcess')
            ->with('foo')
            ->will($this->returnValue(null))
        ;

        $helper = new PidFile($processManager, $this->pidfile);
        $helper->execProcess('foo');
    }

    /**
     * @covers Liip\ProcessManager\PidFile::killProcess
     */
    public function testKillProcess()
    {
        // Do not mock the ProcessManager for this test !
        $pm = new ProcessManager();

        // Spawn a new process
        $child_pid = $pm->execProcess('sleep 10m');

        // Write the new process pid in the lock file and try to kill it
        $helper = new PidFile($pm, $this->pidfile);
        $helper->acquireLock();
        $helper->setPid($child_pid);
        $process_killed = $helper->killProcess();
        $helper->releaseLock();

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


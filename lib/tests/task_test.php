<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * This file contains the unittests for tasks.
 *
 * @package   core
 * @category  phpunit
 * @copyright 2017 Kenneth Hendricks
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Test class for tasks. Not specific to adhoc or scheduled.
 *
 * @package core
 * @category task
 * @copyright 2017 Kenneth Hendricks
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_task_testcase extends advanced_testcase {

    public function setUp() {
        $this->resetAfterTest();
        global $CFG;

        // We want a lock factory that does not support recursion so we can test blocking.
        $CFG->lock_factory = '\\core\\lock\\file_lock_factory';
        $this->cronlockfactory = \core\lock\lock_config::get_lock_factory('cron');

        // Set up a trace buffer to test our verbose strings.
        $trace = new text_progress_trace();
        $this->tracebuffer = new progress_trace_buffer($trace, false);
    }

    public function test_get_all_task_lock_keys_returns_all_scheduled_task_keys() {
        global $DB;
        $expectedtaskkeys = $DB->get_fieldset_sql('SELECT classname FROM {task_scheduled} ORDER BY classname');
        $actualtaskkeys = \core\task\manager::get_all_task_lock_keys();

        foreach ($expectedtaskkeys as $expectedkey) {
            $this->assertContains($expectedkey, $actualtaskkeys);
        }
    }

    public function test_get_all_task_lock_keys_returns_all_adhoc_task_keys() {
        global $DB;
        $expectedtaskkeys = $DB->get_fieldset_sql('SELECT id FROM {task_adhoc} ORDER BY id');
        $actualtaskkeys = \core\task\manager::get_all_task_lock_keys();

        foreach ($expectedtaskkeys as $expectedkey) {
            $expectedkey = 'adhoc_' . $expectedkey;
            $this->assertContains($expectedkey, $actualtaskkeys);
        }
    }

    public function test_get_all_task_locks_can_get_all_locks() {
        $lockkeys = \core\task\manager::get_all_task_lock_keys();
        $expectedlockcount = count($lockkeys);

        $tasklocks = \core\task\manager::get_all_task_locks($this->tracebuffer);
        $tasklockcount = count($tasklocks);

        $this->assertEquals($expectedlockcount, $tasklockcount);

        foreach ($lockkeys as $lockkey) {
            $this->assertArrayHasKey($lockkey, $tasklocks);

            $lock = $tasklocks[$lockkey];
            $this->assertEquals('core\lock\lock', get_class($lock));

            $expectedoutput = "Acquired $lockkey lock";
            $actualoutput = $this->tracebuffer->get_buffer();
            $this->assertContains($expectedoutput, $actualoutput);

            $tasklocks[$lockkey]->release();
        }
    }

    public function test_get_all_task_locks_fails_when_lock_held_and_not_waitforlocks() {
        global $DB;
        // Grab any task lock.
        $taskkey = $DB->get_field('task_scheduled', 'classname', array(), IGNORE_MULTIPLE);
        $tasklock = $this->cronlockfactory->get_lock($taskkey, 0);

        $waitforlocks = false;
        $tasklocks = \core\task\manager::get_all_task_locks($this->tracebuffer, $waitforlocks);

        $expectedoutput = "Could not acquire $taskkey lock";
        $actualoutput = $this->tracebuffer->get_buffer();
        $this->assertContains($expectedoutput, $actualoutput);

        $tasklock->release();
    }
}

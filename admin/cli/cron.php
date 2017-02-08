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
 * CLI cron
 *
 * This script looks through all the module directories for cron.php files
 * and runs them.  These files can contain cleanup functions, email functions
 * or anything that needs to be run on a regular basis.
 *
 * @package    core
 * @subpackage cli
 * @copyright  2009 Petr Skoda (http://skodak.org)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/clilib.php');      // cli only functions
require_once($CFG->libdir.'/cronlib.php');

// now get cli options
list($options, $unrecognized) = cli_get_params(array('help' => false,
                                                     'enable' => false,
                                                     'disable' => false,
                                                     'disable-wait' => false,
                                                     'is-running' => false,
                                                     'verbose' => false),
                                               array('h' => 'help'));

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if (empty($options['verbose'])) {
    $trace = new null_progress_trace();
} else {
    $trace = new text_progress_trace();
}

if ($options['help']) {
    $help =
"Execute periodic cron actions.

Options:
-h, --help            Print out this help
--enable              Enable cron
--disable             Disable cron
--disable-wait        Disable cron and wait until finished
--is-running          Print cron status
--verbose             Print verbose task information for disable-wait and is-running

Example:
\$sudo -u www-data /usr/bin/php admin/cli/cron.php
";

    echo $help;
    die;
} else if ($options['enable']) {
    cron_enable();
    echo "Cron had been enabled for the site.\n";
    die;
} else if ($options['disable']) {
    cron_disable();
    echo "Cron has been disabled for the site.\n";
    die;
} else if ($options['disable-wait']) {
    cron_disable_and_wait($trace);
    echo "Cron is not currently running.\n";
    die;
} else if ($options['is-running']) {
    if (cron_is_running($trace)) {
        echo "Cron is currently running.\n";
    } else {
        echo "Cron is not currently running.\n";
    }
    die;
}

cron_run();

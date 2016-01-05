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
 * Beanstalk check.
 *
 * @package    tool_adhoc
 * @author     Skylar Kelty <S.Kelty@kent.ac.uk>
 * @copyright  2016 University of Kent
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_adhoc\nagios;

/**
 * Check Beanstalkd is running okay.
 */
class beanstalk extends \local_nagios\base_check
{
    public function execute() {
        try {
            $beanstalk = new \tool_adhoc\beanstalk();
            if (!$beanstalk->getConnection()->isServiceListening()) {
                $this->error('Beanstalk isn\'t listening to me!');
            } else {
                $info = $beanstalk->tube_stats();

                if ($info['current-watching'] <= 5) {
                    $this->error('Less than 5 Beanstalk workers found! (found ' . $info['current-watching'] . ')');
                } else {
                    if ($info['current-jobs-ready'] > 50) {
                        $this->warning($info['current-jobs-ready'] . ' jobs queued in Beanstalk.');
                    }

                    if ($info['current-jobs-reserved'] > 50) {
                        $this->warning($info['current-jobs-reserved'] . ' jobs reserved in Beanstalk.');
                    }

                    if ($info['current-jobs-buried'] > 1) {
                        $this->warning($info['current-jobs-buried'] . ' buried jobs in Beanstalk.');
                    }
                }

                $this->set_perf_var('beanstalk_jobs_waiting', $info['current-jobs-ready']);
                $this->set_perf_var('beanstalk_jobs_reserved', $info['current-jobs-reserved']);
                $this->set_perf_var('beanstalk_jobs_buried', $info['current-jobs-buried']);
            }
        } catch (\Exception $e) {
            $this->error('Could not connect to Beanstalk!');
        }
    }
}

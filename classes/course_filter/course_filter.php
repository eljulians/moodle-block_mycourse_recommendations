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
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

defined('MOODLE_INTERNAL') || die();

class course_filter {

    private $minimumweeks;
    private $minimumpreviouscourses;
    private $slidingfactor;
    private $minimummodules;
    private $minimumpreviousstudents;
    private $modulevariationpercentage;


    public function __construct() {

    }

    /**
     * Determines if a course is personalizable or not.
     *
     * @param int $courseid The course to determine if it is personalizable or not.
     */
    public function is_course_personalizable($courseid) {

    }

    public function meets_minimum_weeks($courseid) {

    }

    public function meets_minimum_previous_courses($courseid) {

    }

    public function meets_minimum_modules($courseid) {

    }

    public function meets_minimum_previous_students($courseid) {

    }

    public function meets_module_variation($courseid) {

    }

}

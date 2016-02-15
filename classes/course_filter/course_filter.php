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

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');

use block_mycourse_recommendations\database_helper;

defined('MOODLE_INTERNAL') || die();

class course_filter {

    private static $minimumweeks = 4;
    private static $minimumpreviouscourses = 1;
    private static $minimumpreviousstudents = 20;
    private static $minimumpreviousmodules = self::$minimumpreviousstudents * 2;

    /**
     * Determines if a course is personalizable or not. The logic to determine this is divided in other
     * functions; this function only calls them. If one of these function says that doesn't meet one of
     * the parameters, there's no need to continue calling other functions.
     *
     * @see meets_minimum_weeks($courseid, $db).
     * @see meets_minimum_previous_courses($courseid, $db).
     * @see meets_minimum_resources($courseid, $db).
     * @see meets_minimum_previous_students($courseid, $db).
     * @see meets_resource_variation($courseid, $db).
     * @param int $courseid The course to determine if it is personalizable or not.
     * @return boolean If the given course is personalizable or not.
     */
    public static function is_course_personalizable($courseid) {
        $db = new database_helper();

        $personalizable = true;

        $personalizable .= self::meets_minimum_weeks($courseid, $db);
        if ($personalizable) {
            $personalizable .= self::meets_minimum_previous_courses($courseid, $db);
        }

        if ($personalizable) {
            $personalizable .= self::meets_minimum_resources($courseid, $db);
        }

        if ($personalizable) {
            $personalizable .= self::meets_minimum_previous_students($courseid, $db);
        }

        if ($personalizable) {
            $personalizable .= self::meets_resource_variation($courseid, $db);
        }

        return $personalizable;
    }

    /**
     * Determines if the course duration, in weeks, is long enough to consider it personalizable.
     *
     * @param int $courseid The course to determine if meets the minimum weeks.
     * @param database_helper $db The object with deals with database.
     * @return boolean If the duration of the given course is the needed.
     */
    public static function meets_minimum_weeks($courseid, $db) {

    }

    /**
     * Determines if the course has had the minimum previous teachings in the past years.
     *
     * @param int $courseid The course to determine if has had the minimum teachings before.
     * @param database_helper $db The object with deals with database.
     * @return boolean If the given course has had the minimum teachings before or not.
     */
    public static function meets_minimum_previous_courses($courseid, $db) {

    }

    /**
     * Determines if the course has the minimum number of resources.
     *
     * @param int $courseid The course to determine if meets the minimum modules.
     * @param database_helper $db The object with deals with database.
     * @return boolean If the given course has the minimum resources or not.
     */
    public static function meets_minimum_resources($courseid, $db) {

    }

    /**
     * Determines if the course has had the minimum number of students in previous teachings.
     *
     * @param int $courseid The course to determine if meets the minimum students.
     * @param database_helper $db The object with deals with database.
     * @return boolean If the given course has the minimum students or not.
     */
    public static function meets_minimum_previous_students($courseid, $db) {

    }

    /**
     * Determines if the course meets the resource variation.
     * For the moment, we'll suppose every course meets this.
     *
     * @param int $courseid The course to determine 
     * @param database_helper $db The object with deals with database.
     * @return boolean If the given course meets the resource variation or not.
     */
    public static function meets_resource_variation($courseid, $db) {
        return true;
    }

}

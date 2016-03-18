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
 * Task for cron execution.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations\task;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/weblib.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/decimal_matrix.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/associator/cosine_similarity_associator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/recommendator/simple_recommendator.php');

use block_mycourse_recommendations\database_helper;
use block_mycourse_recommendations\decimal_matrix;
use block_mycourse_recommendations\cosine_similarity_associator;
use block_mycourse_recommendations\simple_recommendator;

/**
 * Class that is being used when the cron job of the plugin is called, for calculating recommendations.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class create_recommendations_task extends \core\task\scheduled_task {

    /**
     * Database helper, to perform actions with the database.
     * @var \block_mycourse_recommendations\database_helper
     */
    private $db;

    /**
     * The interface for dealing with the similarities matrix, whose implementation will be the concrete class
     * implementing the methods.
     * @var \block_mycourse_recommendations\abstract_matrix
     */
    private $matrix;

    /**
     * The interface for generating associations, whose implementation will be the concrete class implementing
     * the methods.
     * @var \block_mycourse_recommendations\abstract_associator
     */
    private $associator;

    /**
     * The abstract class for generation recommendations, whose implementation will be a concrete instance implementing
     * the methods of generating recommendations.
     * @var \block_mycourse_recommendations\abstract_recommendator
     */
    private $recommendator;

    /**
     * Creates the instances of the components to use to calculate the recommendations.
     *
     * @param \text_progress_trace $trace Text output trace.
     */
    protected function initialize($trace) {
        $this->db = new database_helper();
        $this->matrix = new decimal_matrix();
        $this->associator = new cosine_similarity_associator($this->matrix);
        $this->recommendator = new simple_recommendator($this->associator);

        $trace->output('[mycourse]: Components initializated.');
    }

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string Task name.
     */
    public function get_name() {
        return get_string('crontask', 'block_mycourse_recommendations');
    }

    /**
     * Run MyCourse recommendations cron.
     * If it is the first time the task is executed for the given course, then, $this->import_data is called, to import
     * the data from core tables (actually, the need for the importation is decided by database_helper->has_data_to_be_imported).
     */
    public function execute() {
        $trace = new \text_progress_trace();
        $this->initialize($trace);

        $coursestorecommend = $this->db->get_selected_active_courses($trace);

        $trace->output('[mycourse]: Selected active courses for which recommendations will be created:');
        foreach ($coursestorecommend as $course) {
            $trace->output("[mycourse]:\t- $course->courseid");
        }

        foreach ($coursestorecommend as $course) {
            $importdata = $this->db->has_data_to_be_imported($course->courseid);

            if ($importdata) {
                $this->import_data($course->courseid);
            }

            $week = $this->get_current_week();
            $trace->output("[mycourse]: Current week: $week");
            $this->recommendator->create_recommendations($course->courseid, $week, $trace);
        }

        $trace->output('[mycourse]: "MyCourse Recommendations" task execution finished.');
    }

    /**
     * Calculates current year's week number [1, 52].
     *
     * @return int Week number.
     */
    protected function get_current_week() {
        $week = date('W', time());
        $week = intval($week);

        return $week;
    }

    /**
     * Performs the data importation from Moodle core tables (courses, enrolled users, logs). This importation is done only if
     * database_helper->has_data_to_be_imported() returns true.
     *
     * @param int $courseid The current course id for which the data has to be imported.
     */
    protected function import_data($courseid) {
        $courseyear = $this->db->get_course_start_week_and_year($courseid)['year'];
        $previouscourses = $this->db->find_course_previous_teaching_ids_core_tables($courseid, $courseyear);

        foreach ($previouscourses as $previouscourse) {
            $this->db->dump_previous_core_info_to_historic_tables($previouscourse);
        }

        $previouscourses = $this->db->find_course_previous_teachings_ids_historic_tables($courseid, $courseyear);
        $this->db->insert_courses_associations($courseid, $previouscourses);
    }
}

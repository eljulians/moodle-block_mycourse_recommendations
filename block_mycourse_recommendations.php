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
 * Block showing recommendations.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/recommendator/simple_recommendator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/associator/cosine_similarity_associator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/decimal_matrix.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/renderer/recommendations_renderer.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/course_filter/course_filter.php');

use block_mycourse_recommendations\simple_recommendator;
use block_mycourse_recommendations\cosine_similarity_associator;
use block_mycourse_recommendations\decimal_matrix;
use block_mycourse_recommendations\recommendations_renderer;
use block_mycourse_recommendations\database_helper;
use block_mycourse_recommendations\course_filter;

/**
 * Class block_mycourse_recommendations.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_mycourse_recommendations extends block_base {

    /**
     * The interface for dealing with the similarities matrix, whose implementation will be the concrete class
     * implementing the methods.
     * @var block_mycourse_recommendations\abstract_matrix
     */
    private $matrix;

    /**
     * The interface for generating associations, whose implementation will be the concrete class implementing
     * the methods.
     * @var block_mycourse_recommendations\abstract_associator
     */
    private $associator;

    /**
     * The abstract class for generation recommendations, whose implementation will be a concrete instance implementing
     * the methods of generating recommendations.
     * @var block_mycourse_recommendations\abstract_recommendator
     */
    private $recommendator;

    /**
     * Database helper, to perform actions with the database.
     * @var block_mycourse_recommendations\database_helper
     */
    private $db;

    /**
     * Initialises the block.
     */
    public function init() {
        $this->matrix = new decimal_matrix();
        $this->associator = new cosine_similarity_associator($this->matrix);
        $this->recommendator = new simple_recommendator($this->associator);
        $this->db = new database_helper();

        $this->title = get_string('pluginname', 'block_mycourse_recommendations');
    }

    /**
     * Performs all the operations in order to display the block output:
     *  - Checks if it is the first time that the block is loaded in the course, to check if the course is personalizable
     *    or not.
     *  - Checks if the current user is selected to receive the recommendations.
     *  - Retrieves the recommendations from the database.
     *
     * @return string The content of the block.
     */
    public function get_content() {
        global $COURSE, $USER, $DB;

        if ($this->content !== null) {
            return $this->content;
        }

        if (empty($this->instance)) {
            $this->content = '';

            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->text = '';
        $this->content->footer = '';

        $courseyear = $this->db->get_course_start_week_and_year($COURSE->id)['year'];
        $firstinstance = $this->db->is_blocks_first_instance($COURSE->id);

        if ($firstinstance) {
            $this->initialize_course($COURSE->id, $courseyear);
        }

        $personalizable = $this->db->is_course_personalizable($COURSE->id);

        if ($personalizable) {
            $active = $this->db->is_course_active($COURSE->id);
            if ($active) {
                $userselected = $this->db->is_user_selected_for_course($USER->id, $COURSE->id);
                if (!$userselected) {
                    $this->content->text = get_string('usernotselected', 'block_mycourse_recommendations');
                } else {
                    $currentweek = $this->get_current_week();
                    $recommendations = $this->db->get_recommendations($COURSE->id, $USER->id, $currentweek);
                    $this->content->text = recommendations_renderer::render_recommendations($recommendations);
                }
            } else {
                $this->content->text = get_string('inactive', 'block_mycourse_recommendations');
            }
        } else {
            $this->content->text = get_string('notpersonalizable', 'block_mycourse_recommendations');
        }

        return $this->content;
    }

    /**
     * Initializes the course, when is the first instance of the block, looking if it is personalizable or not, and
     * saving this in database.
     *
     * @param int $courseid The course where the first instance of this block has been loaded in.
     * @param int $courseyear The start year of the course.
     */
    private function initialize_course($courseid, $courseyear) {
        $personalizable = course_filter::is_course_personalizable($courseid, $courseyear);

        if ($personalizable) {
            $this->db->insert_course_selection($courseid, $courseyear, 1);
            $this->recommendator->select_students($courseid, $courseyear);

            $previouscourses = $this->db->find_course_previous_teaching_ids_core_tables($courseid, $courseyear);

            if (empty($previouscourses)) {
                $previouscourses = $this->db->find_course_previous_teachings_ids_historic_tables($courseid, $courseyear);
            }
            $this->db->insert_courses_associations($courseid, $previouscourses);

            foreach ($previouscourses as $previouscourse) {
                $this->db->dump_previous_core_info_to_historic_tables($previouscourse);
            }

        } else {
            $this->db->insert_course_selection($courseid, $courseyear, 0);
        }
    }

    /**
     * Calculates current year's week number [1, 52].
     *
     * @return int Week number.
     */
    private function get_current_week() {
        $week = date('W', time());
        $week = intval($week);

        return $week;
    }

}

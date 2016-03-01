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
 * Class block_mycourse_recommendations.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 *             2016 onwards Julen Pardo & Mondragon Unibertsitatea
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

class block_mycourse_recommendations extends block_base {

    private $matrix;
    private $associator;
    private $recommendator;
    private $renderer;
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
     * @return string
     */
    public function get_content() {
        global $COURSE;

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
            $currentweek = $this->get_current_week();
            $recommendations = $this->db->get_recommendations($COURSE->id, $currentweek);
            $this->content->text = recommendations_renderer::render_recommendations($recommendations);
        } else {
            $this->content->text = get_string('notpersonalizable', 'block_mycourse_recommendations');
        }

        return $this->content;
    }

    /**
     * @param int $courseid
     * @param int $courseyear
     */
    private function initialize_course($courseid, $courseyear) {
        $personalizable = course_filter::is_course_personalizable($courseid, $courseyear);

        if ($personalizable) {
            $this->db->insert_course_selection($courseid, $courseyear, 1);
            $this->recommendator->select_students($courseid, $courseyear);
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

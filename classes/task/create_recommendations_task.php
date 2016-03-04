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

namespace block_mycourse_recommendations\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/decimal_matrix.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/associator/cosine_similarity_associator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/recommendator/simple_recommendator.php');

use block_mycourse_recommendations\database_helper;
use block_mycourse_recommendations\decimal_matrix;
use block_mycourse_recommendations\cosine_similarity_associator;
use block_mycourse_recommendations\simple_recommendator;

class create_recommendations_task extends \core\task\scheduled_task {

    private $db;
    private $matrix;
    private $associator;
    private $recommendator;

    /**
     * Creates the instances of the components to use to calculate the recommendations.
     */
    protected function initialize() {
        $this->db = new database_helper();
        $this->matrix = new decimal_matrix();
        $this->associator = new cosine_similarity_associator($this->matrix);
        $this->recommendator = new simple_recommendator($this->associator);
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
     *
     * @see initialize().
     * @see get_current_week().
     */
    public function execute() {
        $this->initialize();

        $coursestorecommend = $this->db->get_selected_active_courses();

        foreach ($coursestorecommend as $course) {
            $week = $this->get_current_week();
            $this->create_recommendations($courseid, $week);
        }
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
}

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
 * Abstract recommendator definition.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/associator/abstract_associator.php');

use block_mycourse_recommendations\database_helper;
use block_mycourse_recommendations\abstract_associator;

/**
 * Class abstract_recommendator for defining the abstract methods for creating associations and recommendations, and
 * the implementation of the students selection for receiving recommendations.
 *
 * @package block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

abstract class abstract_recommendator {

    /**
     * Determines how many weeks to look after the current one, when finding associations and resources to
     * recommend. For example, if the current week is the 4th, and the value of the constant is 1, the data
     * will be queried from the beginning to the 5th week.
     * @var int
     */
    const TIME_WINDOW = 2;

    /**
     * Database helper, to perform actions with the database.
     * @var \block_mycourse_recommendations\database_helper
     */
    protected $db;

    /**
     * The interface for generating associations, whose implementation will be the concrete class implementing
     * the methods.
     * @var \block_mycourse_recommendations\abstract_associator
     */
    protected $associator;

    /**
     * abstract_recommendator constructor.
     *
     * @param \block_mycourse_recommendations\abstract_associator $associatorinstance The instance implementing
     * the methods of associator interface.
     */
    public function __construct($associatorinstance) {
        $this->db = new database_helper();
        $this->associator = $associatorinstance;
    }

    /**
     * Creates the recommendations for the current course and week, looking at the associations of the current users
     * with the historic ones.
     *
     * @param int $courseid
     * @param int $currentweek
     * @param \text_progress_trace $trace Text output trace.
     */
    abstract public function create_recommendations($courseid, $currentweek, $trace = null);

    /**
     * Creates the associations between the current users and historic users, i.e., it decides which historic user is
     * more similar for the current user, to create the recommendations later.
     *
     * @param int $courseid The current course id.
     * @param int $currentweek The current week of the current course.
     * @param \text_progress_trace $trace Text output trace.
     */
    abstract public function create_associations($courseid, $currentweek, $trace = null);

    /**
     * Given the course identificator, selects, randomly, 50% of the students enrolled in the course,
     * who will be the ones receiving the personalized recommendations.
     * "array_rand" function returns the randomly selected keys, not values, so, we have to construct
     * manually the array with random users.
     *
     * @param int $courseid The course to select students from.
     * @param int $year The year the given course belongs to.
     */
    public function select_students($courseid, $year) {
        $coursestudents = $this->db->get_students_from_course($courseid);
        $count = count($coursestudents);

        $selectedstudentsindexes = array_rand($coursestudents, $count / 2);

        $selectedstudents = array();

        foreach ($selectedstudentsindexes as $selectedindex) {
            $selectedstudent = $coursestudents[$selectedindex];
            array_push($selectedstudents, $selectedstudent);
        }
        $this->db->insert_selections($selectedstudents, $courseid, $year);
    }

}

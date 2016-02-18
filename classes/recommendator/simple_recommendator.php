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

require_once('abstract_recommendator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/abstract_matrix.php');

use block_mycourse_recommendations\abstract_recommendator;
use block_mycourse_recommendations\database_helper;

class simple_recommendator extends abstract_recommendator {

    public function __construct($associatorinstance) {
        parent::__construct($associatorinstance);
        null; // The codechecker will throw a warning if we don't do something more apart from calling parent's constructor...
    }

    public function create_recommendations($courseid, $currentweek) {

    }

    /**
     * Creates the associations between the current users and historic users. In this simple_recommendator implementation,
     * each current user is associated with a UNIQUE previous user.
     *
     * @see get_selected_users($courseid) in database_helper.php.
     * @see get_course_start_week_and_year($courseid) in database_helper.php.
     * @see query_data($courseid, $year, $startweek, $currentweek) in database_helper.php.
     * @see find_course_previous_teachings_ids($courseid, $year) in database_helper.php.
     * @see create_associations_matrix in abstract_associator.php.
     * @see insert_associations in database_helper.php.
     * @param int $courseid The current course id.
     * @param int $currentweek The current week of the current course.
     */
    public function create_associations($courseid, $currentweek) {
        global $DB;

        $users = $this->db->get_selected_users($courseid);

        $coursedates = $this->db->get_course_start_week_and_year($courseid);
        $startweek = $coursedates['week'];
        $year = $coursedates['year'];

        $currentdata = $this->db->query_data($courseid, $year, $startweek, $currentweek);

        $previouscourses = $this->db->find_course_previous_teachings_ids($courseid, $year);
        $previouscourse = max($previouscourses);

        $coursedates = $this->db->get_course_start_week_and_year($previouscourse);
        $startweek = $coursedates['week'];
        $year = $coursedates['year'];

        $previousdata = $this->db->query_data($previouscourse, $year, $startweek, $currentweek);

        // We get the association matrix, where the rows will be the current users id; the columns, the previous users;
        // and the values, the simmilarity coefficient.
        $associationmatrix = $this->associator->create_associations_matrix($currentdata, $previousdata);

        $number = count($associationmatrix);
        $currentusersids = array();
        $historicusersids = array();

        // We have to find the highest coefficient of the relation of each current user with each previous student.
        foreach ($associationmatrix as $currentuser => $similarities) {
            $highestsimilarityindex = array_keys($similarities, max($similarities));

            $associatedhistoric = $similarities[$highestsimilarityindex[0]];

            array_push($currentusersids, $currentuser);
            // The key, user id, of the highest similarity coefficient, will be the most similar user.
            array_push($historicusersids, intval($highestsimilarityindex[0]));
        }

        // Finally, we call the function that will insert the associations into the database.
        $this->db->insert_associations($number, $currentusersids, $courseid, $historicusersids, $previouscourse, $currentweek);
    }
}

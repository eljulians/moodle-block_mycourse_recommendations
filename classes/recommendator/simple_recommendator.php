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

    /**
     * Creates the recommendations for the current course and week, first, creating and retrieving the associations
     * between the current and previous users; second, for each association, querying the logviews of the resources
     * of the previous associated user; third, stablishing a priority order of these resources by views, being 0 the
     * most viewed and thereforo the one with most priority; and fourth, defining the data structure to make the
     * insertion into the database.
     *
     * @see create_associations($courseid, $currentweek).
     * @see get_associations($courseid, $currentweek) in database_helper.php.
     * @see get_course_start_week_and_year($courseid) in database_helper.php.
     * @param int $courseid
     * @param int $currentweek
     */
    public function create_recommendations($courseid, $currentweek) {
        $this->create_associations($courseid, $currentweek);

        $associations = $this->db->get_associations($courseid, $currentweek);

        $recommendations = array();
        $recommendationindex = 0;

        foreach ($associations as $associationid => $association) {
            $userid = $association->historic_userid;
            $previouscourseid = $association->historic_courseid;
            $year = $this->db->get_course_start_week_and_year($previouscourseid)['year'];

            $records = $this->db->query_data($previouscourseid, $year, $currentweek, $currentweek + 1, $userid);
            $logviews = array();
            $index = 0;

            // We save the view of each resource in an associative array, only if it has been seen at least once.
            foreach ($records as $record) {
                if ($record->get_logviews > 0) {
                    $logviews[$record->moduleid] = $record->get_logviews();
                }
            }

            rsort($logviews);

            $recommendations[$recommendationindex] = new stdClass();
            $recommendations[$recommendationindex]->number = count($logviews);
            $recommendations[$recommendationindex]->associationids = array();
            $recommendations[$recommendationindex]->resourcesids = array();
            $recommendations[$recommendationindex]->priorities = array();

            $index = 0;
            foreach ($logviews as $resourceid => $views) {
                $recommendations[$recommendationindex]->associationids[$index] = $associationid;
                $recommendations[$recommendationindex]->resourcesids[$index] = $resourceid;
                $recommendations[$recommendationindex]->priorities[$index] = $index; // Index works like priority also.

                $index++;
            }

            $recommendationindex++;
        }

        foreach ($recommendations as $index => $recommendation) {
            $this->db->insert_recommendations($recommendation->number, $recommendation->associationids,
                                              $recommendation->resourcesids, $recommendation->priorities);
        }
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

        $associatedresources = $this->associate_resources($previousdata, $currentdata);
        $previousdata = $associatedresources['previous'];
        $currentdata = $associatedresources['current'];

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

    /**
     * Makes the relations between the resources of current and previous courses. To determine the association between two
     * resources, the name is used. So, the names must match exactly to consider the association.
     * Current resources are iterated, finding the relation with previous. The result is the same received data, but with the
     * associated resources aligned in the arrays; in the same position.
     *
     * @param \block_mycourse_recommendations\query_result $previousdata The data of previous course retrieved from database.
     * @param \block_mycourse_recommendations\query_result $currentdata The data of current course retrieved from database.
     * @return array An array with the transformation of the received arrays. Since PHP 5.4 the pass of arguments by reference
     * is not allowed, so this is the only way to return the two arrays :( .
     */
    public function associate_resources($previousdata, $currentdata) {
        $previousresources = array();
        $currentresources = array();
        $currenttransformedindex = 0;

        foreach ($currentdata as $currentindex => $currentresource) {
            foreach ($previousdata as $previousindex => $previousresource) {
                if ($currentresource->get_modulename() === $previousresource->get_modulename()) {
                    if (!in_array($previousresource, $previousresources)) {
                        array_push($previousresources, $previousresource);
                    }
                    if (!in_array($currentresource, $currentresources)) {
                        array_push($currentresources, $currentresource);
                    }
                }
            }
        }

        $resources = array();
        $resources['previous'] = $previousresources;
        $resources['current'] = $currentresources;

        return $resources;
    }
}

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
 * Implementation of block_mycourse_recommendations\abstract_recommendator in the simplest way.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/weblib.php');
require_once('abstract_recommendator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/abstract_matrix.php');

use block_mycourse_recommendations\abstract_recommendator;
use block_mycourse_recommendations\database_helper;

/**
 * Class simple_recommendator for implementing the specific methods of association and recommendations creation in
 * the simplest way.
 *
 * @package block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class simple_recommendator extends abstract_recommendator {

    /**
     * simple_recommendator constructor.
     *
     * @param \block_mycourse_recommendations\abstract_associator $associatorinstance The instance implementing the methods
     * @param \block_mycourse_recommendations\abstract_associator $associatorinstance The instance implementing the methods
     * for calculating the associations.
     */
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
     * @see query_data($courseid, $year, $coursestartweek, $currentweek, $userid = null, $ignoreweeks = false,
     * $onlyunviewed = false).
     * @see keep_latest_logviews($resources).
     * @see save_logviews_by_resource($previousresources, $currentresources).
     * @see insert_recommendations($number, $associationids, $resourcesids, $priorities) in database_helper.php.
     * @param int $courseid
     * @param int $currentweek
     * @param \text_progress_trace $trace Text output trace.
     * @return boolean False if any association could be done; true if yes.
     */
    public function create_recommendations($courseid, $currentweek, $trace = null) {
        $associationscreated = $this->create_associations($courseid, $currentweek, $trace);

        if ($associationscreated) {
            $associations = $this->db->get_associations($courseid, $currentweek);

            $recommendations = array();
            $recommendationindex = 0;

            foreach ($associations as $associationid => $association) {
                $trace->output("[mycourse " . date('d/m/Y H:i:s') . "]: Creating recommendations for current user "
                    . "'$association->current_userid', associated with historic user '$association->historic_userid'.");

                $userid = $association->historic_userid;
                $previouscourseid = $association->historic_courseid;
                $year = $this->db->get_course_start_week_and_year($previouscourseid, true)['year'];
                $coursestartweek = $this->db->get_course_start_week_and_year($courseid)['week'];

                $lowerlimitweek = $currentweek - parent::TIME_WINDOW;
                $yearchange = $coursestartweek > $currentweek;

                $upperlimitweek = $currentweek;
                if ($yearchange) {
                    $upperlimitweek = $currentweek + 52;
                }
                $upperlimitweek += parent::TIME_WINDOW;

                $trace->output("[mycourse " . date('d/m/Y H:i:s') . "]: Querying data for historic user $userid from weeks "
                    . "'$lowerlimitweek' to '$upperlimitweek'.");

                $previousrecords = $this->db->query_historic_course_data_grouped_by_views($previouscourseid, $year, $lowerlimitweek,
                                                                                          $upperlimitweek, $userid);

                $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Previous query has finished.');
                $trace->output("[mycourse " . date('d/m/Y H:i:s') . "]: Querying data for current user "
                    . "$association->current_userid from resources that have not been viewed.");

                $currentrecords = $this->db->query_current_not_viewed_resources($association->current_userid, $courseid);

                $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Previous query has finished.');
                $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Starting the association of common resources in both '
                    . 'years, to determine which resources of previous year correspond to current\'s, to decide the priority of '
                    . 'each recommendation.');

                $associatedresources = $this->associate_resources($previousrecords, $currentrecords);
                $previousresources = $associatedresources['previous'];
                $currentresources = $associatedresources['current'];

                $logviews = $this->save_logviews_by_resource($previousresources, $currentresources);

                $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Association of resources ended.');

                $recommendations[$recommendationindex] = new \stdClass();
                $recommendations[$recommendationindex]->number = count($logviews);
                $recommendations[$recommendationindex]->associationids = array();
                $recommendations[$recommendationindex]->resourcesids = array();
                $recommendations[$recommendationindex]->priorities = array();

                $index = 0;
                foreach ($logviews as $currentresourceid => $views) {
                    $recommendations[$recommendationindex]->associationids[$index] = $associationid;
                    $recommendations[$recommendationindex]->resourcesids[$index] = $currentresourceid;
                    $recommendations[$recommendationindex]->priorities[$index] = $index;

                    $index++;
                }

                $trace->output("[mycourse " . date('d/m/Y H:i:s') . "]: Recommendation calculation for user "
                    . "$association->current_userid have ended.");

                $recommendationindex++;
            }

            $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: All the recommendations have been created. Total count of '
                . 'recommendations: ' . count($recommendations));
            $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Inserting recommendations into database...');

            foreach ($recommendations as $index => $recommendation) {
                $this->db->insert_recommendations($recommendation->number, $recommendation->associationids,
                    $recommendation->resourcesids, $recommendation->priorities);
            }

            $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: The recommendations have been inserted into database.');

            return true;
        } else {
            $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: No recommendations will be created because no association '
                . 'could be done.');

            return false;
        }
    }

    /**
     * Creates the associations between the current users and historic users. In this simple_recommendator implementation,
     * each current user is associated with a UNIQUE previous user.
     * If $currentweek is the week of the year after the course start, we have to add 52 to $currentweek, otherwise, the
     * query won't return results, because $currentweek will be lower than the start week.
     *
     * @see get_selected_users($courseid) in database_helper.php.
     * @see get_course_start_week_and_year($courseid) in database_helper.php.
     * @see query_data($courseid, $year, $startweek, $currentweek) in database_helper.php.
     * @see find_course_previous_teachings_ids($courseid, $year) in database_helper.php.
     * @see create_associations_matrix in abstract_associator.php.
     * @see insert_associations in database_helper.php.
     * @param int $courseid The current course id.
     * @param int $currentweek The current week of the current course.
     * @param \text_progress_trace $trace Text output trace.
     * @return boolean False if no association could be done; true if yes.
     */
    public function create_associations($courseid, $currentweek, $trace = null) {
        $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Creating associations.');
        $selectedusers = $this->db->get_selected_users($courseid);

        $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Selected users to receive recommendations:');

        foreach ($selectedusers as $selecteduser) {
            $trace->output("[mycourse]: \t- $selecteduser");
        }

        $coursedates = $this->db->get_course_start_week_and_year($courseid, false);
        $startweek = $coursedates['week'];
        $year = $coursedates['year'];
        $trace->output("[mycourse " . date('d/m/Y H:i:s') . "]: Course start year: $year; start week: $startweek");

        $yearchange = $startweek > $currentweek;
        $endweek = $currentweek;
        if ($yearchange) {
            $endweek = $currentweek + 52;
        }
        $endweek += parent::TIME_WINDOW;

        $trace->output("[mycourse " . date('d/m/Y H:i:s') . "]: Log data for course '$courseid' will be queried with the "
            . "followingparameters: year: $year; from start week: $startweek; to end week: $endweek");
        $currentdata = $this->db->query_data($courseid, $year, $startweek, $endweek);

        $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Previous query has finished.');

        // We keep only the users that are selected to receive the recommendations.
        $currentselecteddata = array();
        foreach ($currentdata as $currentuserrow) {
            $isselecteduser = in_array($currentuserrow->get_userid(), $selectedusers);
            if ($isselecteduser) {
                array_push($currentselecteddata, $currentuserrow);
            }
        }

        $previouscourses = $this->db->get_associated_courses($courseid);

        $previouscourse = max($previouscourses);
        $trace->output("[mycourse " . date('d/m/Y H:i:s') . "]: Current course: '$courseid' will use the historic course "
            . "'$previouscourse' for the associations.");

        $coursedates = $this->db->get_course_start_week_and_year($previouscourse, true);
        $startweek = $coursedates['week'];
        $year = $coursedates['year'];

        $previousresources = $this->db->query_historic_resources_info($previouscourse);

        $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Starting the association of common resources in both '
            . 'years, to determine which resources of previous year correspond to current\'s, to decide which can be candidate '
            . 'to be recommended.');

        $associatedresources = $this->associate_resources($previousresources, $currentselecteddata);
        $previousresources = $associatedresources['previous'];
        $currentdata = $associatedresources['current'];

        $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Resource association ended.');

        $onlyrecommendable = true;
        $previousdata = $this->db->query_historic_course_data($previouscourse, $year, $startweek, $endweek, null, true,
            $onlyrecommendable, $previousresources);

        // We get the association matrix, where the rows will be the current users id; the columns, the previous users;
        // and the values, the simmilarity coefficient.
        $this->associator->set_currentweek($currentweek);

        $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Starting association matrix creation, where each current user '
            . ' will be associated with the most similar previous user.');

        $associationmatrix = $this->associator->create_associations_matrix($currentdata, $previousdata , $trace);

        $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Association matrix creation ended.');

        if (!empty($associationmatrix)) {
            $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Associations between current and historic users were made '
                . 'successfully.');

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

                $trace->output("[mycourse]: Current user '$currentuser' has been associated with historic user"
                    . " '$highestsimilarityindex[0]'.");
            }

            // Finally, we call the function that will insert the associations into the database.
            $this->db->insert_associations($number, $currentusersids, $courseid, $historicusersids, $previouscourse, $currentweek);

            $trace->output('[mycourse ' . date('d/m/Y H:i:s') . ']: Users associations have been inserted into database.');

            return true;
        } else {
            $trace->output("[mycourse " . date('d/m/Y H:i:s') . "]: No associations could be done because the current course ".
                "'$courseid' and the historic course '$previouscourse' do not share any resources.");
            return false;
        }
    }

    /**
     * Removes the repeated resources from the array. Two resources would be considered equals if they have the same name and
     * resource type.
     *
     * @param $data
     * @return mixed
     */
    protected function discard_repeated_resources($data) {
        $auxdata = $data;
        $discardedrows = 0;

        for ($index = 0; $index < count($data); $index++) {
            $referencerow = $data[$index];
            for ($innerindex = $index + 1; $innerindex < count($data); $innerindex++) {
                $comparingrow = $data[$innerindex];

                $samename = $referencerow->get_modulename() === $comparingrow->get_modulename();
                $sametype = $referencerow->get_moduletype() === $comparingrow->get_moduletype();

                if ($samename && $sametype) {
                    $samenamebutdistinctid = $referencerow->get_moduleid() !== $comparingrow->get_moduleid();

                    if ($samenamebutdistinctid) {
                        unset($auxdata[$innerindex - $discardedrows]);
                        $discardedrows--;
                    }
                }
            }
        }

        return $auxdata;
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

        sort($previousdata);
        sort($currentdata);

        foreach ($currentdata as $currentindex => $currentresource) {
            foreach ($previousdata as $previousindex => $previousresource) {
                $samename = $currentresource->get_modulename() === $previousresource->get_modulename();
                $sametype = $currentresource->get_moduletype() === $previousresource->get_moduletype();

                if ($samename && $sametype) {
                    array_push($previousresources, $previousresource);
                    array_push($currentresources, $currentresource);

                    continue;
                }
            }
        }

        $resources = array();
        $resources['previous'] = $previousresources;
        $resources['current'] = $currentresources;

        return $resources;
    }

    /**
     * Discards the older results of a query, removing those duplicated rows for a resource that have less views compared with
     * other views of the same resource.
     *
     * @param \block_mycourse_recommendations\query_result $previousresources The queried data of the previous course.
     * @param \block_mycourse_recommendations\query_result $currentresources The queried data of the current course.
     * @return array The received queried data, but only with the latest
     * views of the resources.
     */
    protected function keep_latest_logviews($previousresources, $currentresources) {
        $auxpreviousresources = $previousresources;
        $auxcurrentresources = $currentresources;

        foreach ($previousresources as $previousindex => $previousresource) {
            foreach ($auxpreviousresources as $auxindex => $aux) {
                if ($aux->get_moduleid() === $previousresource->get_moduleid()) {
                    if ($previousresource->get_logviews() > $aux->get_logviews()) {
                        unset($auxpreviousresources[$auxindex]);
                        unset($auxcurrentresources[$auxindex]);
                    }
                }
            }
        }

        return array('previous' => array_values($auxpreviousresources), 'current' => array_values($auxcurrentresources));
    }

    /**
     * Saves the views of each resource in an associative array with the resourceid as key, sorted descendently, only if it
     * has been seen at least once.
     *
     * @param blocks_mycourse_recommendations\query_result $previousresources The queried data of the previous course.
     * @param blocks_mycourse_recommendations\query_result $ previousresources The queried data of the current course.
     * @return array The logviews of each resource.
     */
    protected function save_logviews_by_resource($previousresources, $currentresources) {
        $logviews = array();
        $index = 0;

        foreach ($previousresources as $previousresource) {
            if ($previousresource->get_logviews() > 0) {
                $logviews[$currentresources[$index]->get_moduleid()] = $previousresource->get_logviews();
            }

            $index++;
        }

        arsort($logviews);

        return $logviews;
    }
}

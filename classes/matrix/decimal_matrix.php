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
 * @package   block_mycourse_recommendations
 * @copyright 2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

defined('MOODLE_INTERNAL') || die();

require_once('abstract_matrix.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/query_result.php');

use block_mycourse_recommendations\abstract_matrix;
use block_mycourse_recommendations\query_result;

class decimal_matrix implements abstract_matrix {

    /**
     * Transforms the data of a course fetched from database. The query MUST RETURN THE RESULTS ORDERED BY
     * USERS ID.
     * The "userid" and "moduleid" are casted to string because we want to have an associative matrix.
     *
     * @param array $queryresults "query_result" objects.
     * @return array A matrix of the log views, with the users as rows, and the modules (resources) as columns.
     */
    public function transform_queried_data($queryresults) {
        $users = array();
        $previoususer = -1;

        for ($index = 0; $index < count($queryresults); $index++) {
            $currentuser = (string)$queryresults[$index]->get_userid();

            // If we get a new user...
            if ($currentuser !== $previoususer) {
                $user = array();
            }

            $module = (string)$queryresults[$index]->get_moduleid();
            $views = $queryresults[$index]->get_logviews();

            // We save the $views number in [$user] row and [$module] column of the matrix.
            $user[$module] = $views;

            $lastuser = $index + 1 === count($queryresults);

            if ($lastuser) {
                $users[$currentuser] = $user;
            } else {
                $differentusercoming = $currentuser != $queryresults[$index + 1]->get_userid();

                // If the next user in the array is different from the current, we save the changes of the current.
                if ($differentusercoming) {
                    $users[$currentuser] = $user;
                }
  
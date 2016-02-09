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

use block_mycourse_recommendations\abstract_matrix;

class decimal_matrix implements abstract_matrix {

    /**
     * Transforms the data of a course fetched from database. The query MUST RETURN THE RESULTS ORDERED BY
     * USERS ID.
     *
     * @param array $data The data queried from database.
     * @return array A matrix of the log views, with the users as rows, and the modules (resources) as columns.
     */
    public function transform_queried_data($data) {
        $users = array();
        $previoususer = -1;

        for ($index = 0; $index < count($data); $index++) {
            $currentuser = $data[$index]->userid;

            // If we get a new user...
            if ($currentuser !== $previoususer) {
                $user = array();
            }

            $module = $data[$index]->moduleid;
            $views = $data[$index]->log_views;

            // We save the $views number in [$user] row and [$module] column of the matrix.
            $user[$module] = $views;

            $lastuser = $index + 1 === count($data);

            if ($lastuser) {
                $users[$currentuser] = $user;
            } else {
                $differentusercoming = $currentuser != $data[$index + 1]->userid;

                // If the next user in the array is different from the current, we save the changes of the current.
                if ($differentusercoming) {
                    $users[$currentuser] = $user;
                }
            }

            $previoususer = $data[$index]->userid;
        }

        return $users;
    }

}

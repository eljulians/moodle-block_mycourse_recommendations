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
 * Decimal matrix for handling logview information in decimal number format.
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

/**
 * Class decimal_matrix for implementation of abstract_matrix for logview handling in decimal number format.
 *
 * @package   block_mycourse_recommendations
 * @copyright 2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class decimal_matrix implements abstract_matrix {

    /**
     * Transforms the data of a course fetched from database, creating a matrix, where the rows will be the users ids;
     * the columns, the resources ids; and the values, the views.
     *
     * @param \block_mycourse_recommendations\query_result $queryresults The query result, with the logviews of the users for the
     * given course.
     * @return array A matrix of the log views, with the users as rows, and the modules (resources) as columns.
     */
    public function transform_queried_data($queryresults) {
        $users = array();

        foreach ($queryresults as $row) {
            $currentuser = $row->get_userid();
            $resource = $row->get_moduleid();
            $views = $row->get_logviews();

            if (!isset($users[$currentuser])) {
                $users[$currentuser] = array();
            }

            $users[$currentuser][$resource] = $views;
        }

        return $users;
    }

}

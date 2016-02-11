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
 * Unit tests for decimal data matrix of mycourse_recommendations block.
 *
 * @package    block_mycourse_recommendations
 * @category   phpunit
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/decimal_matrix.php'); // Include the code to test.
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/query_result.php');

use block_mycourse_recommendations\decimal_matrix;
use block_mycourse_recommendations\query_result;

/**
 * Test cases for block_mycourse_recommendations for decimal data matrix.
 */
class block_mycourse_recommendations_decimal_matrix_testcase extends advanced_testcase {

    protected $matrix;

    /**
     * Set up the test environment.
     */
    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();

        $this->matrix = new decimal_matrix();
    }

    protected function tearDown() {
        $this->matrix;
        parent::tearDown();
    }

    /**
     * Test that the data retrieved from database is properly transformed to user/module matrix.
     */
    public function test_transform_queried_data() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Input for the function. We create manually an input instead of using
        // database_helper->query_data, because it will probably be quite difficult to
        // generate all the necessary data the query needs. So, we emulate that function's
        // return value.
        $input = array();

        $input[0] = new query_result(1, 1, 10, 'module 10', 45, 0);

        $input[1] = new query_result(1, 1, 20, 'module 20', 31, 0);

        $input[2] = new query_result(1, 1, 30, 'module 30', 5, 0);

        $input[3] = new query_result(2, 1, 10, 'module 10', 16, 0);

        $input[4] = new query_result(3, 1, 20, 'module 20', 57, 0);

        $input[5] = new query_result(3, 1, 30, 'module 30', 24, 0);

        $input[6] = new query_result(4, 1, 10, 'module 10', 68, 0);

        $output = $this->matrix->transform_queried_data($input);

        // Okay, let's assert this thing. $output should now be a 4x3 matrix, because we have generated
        // an input with 4 different userids, and 3 different moduleids. So, we check matrix's values
        // (log views), accessing them by [$userid][$moduleid]. The access to the matrix is hardcoded because
        // doing like "$output[$input[0]->userid][$input[0]->moduleid], $input[0]->log_views" is so unintelligible.
        $this->assertEquals($output[1][10], 45);
        $this->assertEquals($output[1][20], 31);
        $this->assertEquals($output[1][30], 5);

        $this->assertEquals($output[2][10], 16);

        $this->assertEquals($output[3][20], 57);
        $this->assertEquals($output[3][30], 24);

        $this->assertEquals($output[4][10], 68);
    }

}

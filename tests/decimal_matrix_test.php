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
use block_mycourse_recommendations\decimal_matrix;

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

        $input[0] = new stdClass();
        $input[0]->userid = 1;
        $input[0]->moduleid = 10;
        $input[0]->log_views = 45;

        $input[1] = new stdClass();
        $input[1]->userid = 1;
        $input[1]->moduleid = 20;
        $input[1]->log_views = 31;

        $input[2] = new stdClass();
        $input[2]->userid = 1;
        $input[2]->moduleid = 30;
        $input[2]->log_views = 5;

        $input[3] = new stdClass();
        $input[3]->userid = 2;
        $input[3]->moduleid = 10;
        $input[3]->log_views = 16;

        $input[4] = new stdClass();
        $input[4]->userid = 3;
        $input[4]->moduleid = 20;
        $input[4]->log_views = 57;

        $input[5] = new stdClass();
        $input[5]->userid = 3;
        $input[5]->moduleid = 30;
        $input[5]->log_views = 24;

        $input[6] = new stdClass();
        $input[6]->userid = 4;
        $input[6]->moduleid = 10;
        $input[6]->log_views = 68;

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

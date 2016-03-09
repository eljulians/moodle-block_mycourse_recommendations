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
 * Unit tests for cosine similarity associations of mycourse_recommendations block.
 *
 * @package    block_mycourse_recommendations
 * @category   phpunit
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/associator/cosine_similarity_associator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/decimal_matrix.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/query_result.php');

use block_mycourse_recommendations\cosine_similarity_associator;
use block_mycourse_recommendations\decimal_matrix;
use block_mycourse_recommendations\query_result;

/**
 * Test cases for block_mycourse_recommendations for cosine similarty associations.
 */
class block_mycourse_recommendations_cosine_similarity_associator_testcase extends advanced_testcase {

    protected $associator;
    protected $matrix;

    /**
     * Set up the test environment.
     */
    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();

        $this->matrix = new decimal_matrix();
        $this->associator = new cosine_similarity_associator($this->matrix);
    }

    protected function tearDown() {
        $this->databasehelper = null;
        $this->course = null;
        $this->users = null;
        $this->resource = null;
        parent::tearDown();
    }

    /**
     * Gets class' method by name. Seems that for creating the ReflectionClass, it's necessary to
     * specify the full namespace.
     */
    protected static function get_method($name) {
        $class = new \ReflectionClass('\block_mycourse_recommendations\cosine_similarity_associator');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function test_create_associations_matrix() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $expected = array(array());
        $delta = 0.001;

        $historicdata[0] = new query_result(1, 1, 10, 'module 10', 54, 0);
        $historicdata[1] = new query_result(1, 1, 11, 'module 11', 1, 0);
        $historicdata[2] = new query_result(1, 1, 12, 'module 12', 35, 0);
        $historicdata[3] = new query_result(1, 1, 13, 'module 13', 65, 0);

        $historicdata[4] = new query_result(2, 1, 10, 'module 10', 145, 0);
        $historicdata[5] = new query_result(2, 1, 11, 'module 11', 65, 0);
        $historicdata[6] = new query_result(2, 1, 12, 'module 12', 46, 0);
        $historicdata[7] = new query_result(2, 1, 13, 'module 13', 123, 0);

        $historicdata[8] = new query_result(3, 1, 10, 'module 10', 6, 0);
        $historicdata[9] = new query_result(3, 1, 11, 'module 11', 76, 0);
        $historicdata[10] = new query_result(3, 1, 12, 'module 12', 45, 0);
        $historicdata[11] = new query_result(3, 1, 13, 'module 13', 12, 0);

        $historicdata[12] = new query_result(4, 1, 10, 'module 10', 65, 0);
        $historicdata[13] = new query_result(4, 1, 11, 'module 11', 1254, 0);
        $historicdata[14] = new query_result(4, 1, 12, 'module 12', 54, 0);
        $historicdata[15] = new query_result(4, 1, 13, 'module 13', 54, 0);

        $historicdata[16] = new query_result(5, 1, 10, 'module 10', 12, 0);
        $historicdata[17] = new query_result(5, 1, 11, 'module 11', 76, 0);
        $historicdata[18] = new query_result(5, 1, 12, 'module 12', 45, 0);
        $historicdata[19] = new query_result(5, 1, 13, 'module 13', 78, 0);

        $historicdata[20] = new query_result(6, 1, 10, 'module 10', 32, 0);
        $historicdata[21] = new query_result(6, 1, 11, 'module 11', 489, 0);
        $historicdata[22] = new query_result(6, 1, 12, 'module 12', 12, 0);
        $historicdata[23] = new query_result(6, 1, 13, 'module 13', 37, 0);

        $currentdata[0] = new query_result(100, 1, 10, 'module 10', 456, 0);
        $currentdata[1] = new query_result(100, 1, 11, 'module 11', 71, 0);
        $currentdata[2] = new query_result(100, 1, 12, 'module 12', 23, 0);
        $currentdata[3] = new query_result(100, 1, 13, 'module 13', 47, 0);

        $currentdata[4] = new query_result(101, 1, 10, 'module 10', 31, 0);
        $currentdata[5] = new query_result(101, 1, 11, 'module 11', 217, 0);
        $currentdata[6] = new query_result(101, 1, 12, 'module 12', 41, 0);
        $currentdata[7] = new query_result(101, 1, 13, 'module 13', 34, 0);

        $currentdata[8] = new query_result(102, 1, 10, 'module 10', 31, 0);
        $currentdata[9] = new query_result(102, 1, 11, 'module 11', 87, 0);
        $currentdata[10] = new query_result(102, 1, 12, 'module 12', 64, 0);
        $currentdata[11] = new query_result(102, 1, 13, 'module 13', 12, 0);

        $expected = array('100' => array('1' => 0.6721,
                                         '2' => 0.8102,
                                         '3' => 0.2345,
                                         '4' => 0.2096,
                                         '5' => 0.283,
                                         '6' => 0.2248),
                          '101' => array('1' => 0.2683,
                                         '2' => 0.5303,
                                         '3' => 0.9394,
                                         '4' => 0.9803,
                                         '5' => 0.7995,
                                         '6' => 0.9816),
                          '102' => array('1' => 0.4625,
                                         '2' => 0.6255,
                                         '3' => 0.9729,
                                         '4' => 0.8105,
                                         '5' => 0.8068,
                                         '6' => 0.8055)
                          );

        // We have to set a week, even if it doesn't affect to this test, otherwise, not null constraint of
        // {block_mycourse_similarities} will break.
        $this->associator->set_currentweek(1);
        $actual = $this->associator->create_associations_matrix($currentdata, $historicdata);

        // It seems that we have assert every value to make the assertion behave properly.
        // It doesn't matter if we take expected's or output's keys; they must be the same.
        foreach (array_keys($actual) as $row) {
            foreach (array_keys($actual[$row]) as $column) {
                $this->assertEquals($expected[$row][$column], $actual[$row][$column], '', $delta);
            }
        }
    }

    public function test_dot_product() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $dotproduct = self::get_method('dot_product');
        $vector1 = array();
        $vector2 = array();
        $expected = 10806;

        $vector1[0] = 1;
        $vector1[1] = 5;
        $vector1[2] = 67;
        $vector1[3] = 14;
        $vector2[0] = 7;
        $vector2[1] = 71;
        $vector2[2] = 154;
        $vector2[3] = 9;

        $actual = $dotproduct->invokeArgs($this->associator, array($vector1, $vector2));

        $this->assertEquals($expected, $actual);
    }

    public function test_vector_module() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $vectormodule = self::get_method('vector_module');
        $vector = array();
        $expected = 68.6367;
        $delta = 0.0001;

        $vector[0] = 1;
        $vector[1] = 5;
        $vector[2] = 67;
        $vector[3] = 14;

        $actual = $vectormodule->invokeArgs($this->associator, array($vector));

        $this->assertEquals($expected, $actual, '', $delta);
    }

    public function test_cosine_similarity() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $cosinesimilarity = self::get_method('cosine_similarity');
        $vector1 = array();
        $vector2 = array();
        $expected = 0.9263;
        $delta = 0.0001;

        $vector1[0] = 1;
        $vector1[1] = 5;
        $vector1[2] = 67;
        $vector1[3] = 14;
        $vector2[0] = 7;
        $vector2[1] = 71;
        $vector2[2] = 154;
        $vector2[3] = 9;

        $actual = $cosinesimilarity->invokeArgs($this->associator, array($vector1, $vector2));

        $this->assertEquals($expected, $actual, '', $delta);
    }

}

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

use block_mycourse_recommendations\cosine_similarity_associator;
use block_mycourse_recommendations\decimal_matrix;

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

        $historicdata = array('1' => array('10' => 54,
                                           '11' => 1,
                                           '12' => 35,
                                           '13' => 65),
                              '2' => array('10' => 145,
                                           '11' => 65,
                                           '12' => 46,
                                           '13' => 123),
                              '3' => array('10' => 6,
                                           '11' => 76,
                                           '12' => 45,
                                           '13' => 12),
                              '4' => array('10' => 65,
                                           '11' => 1254,
                                           '12' => 54,
                                           '13' => 54),
                              '5' => array('10' => 12,
                                           '11' => 76,
                                           '12' => 45,
                                           '13' => 78),
                              '6' => array('10' => 32,
                                           '11' => 489,
                                           '12' => 12,
                                           '13' => 37)
                            );

        $currentdata = array('100' => array('10' => 456,
                                            '11' => 71,
                                            '12' => 23,
                                            '13' => 47),
                             '101' => array('10' => 31,
                                            '11' => 217,
                                            '12' => 41,
                                            '13' => 34),
                             '102' => array('10' => 31,
                                            '11' => 87,
                                            '12' => 64,
                                            '13' => 12)
                             );

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

        $output = $this->associator->create_associations_matrix($currentdata, $historicdata);

        // It seems that we have assert every value to make the assertion behave properly.
        // It doesn't matter if we take expected's or output's keys; they must be the same.
        foreach (array_keys($output) as $row) {
            foreach (array_keys($output[$row]) as $column) {
                $this->assertEquals($output[$row][$column], $expected[$row][$column], '', $delta);
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

        $output = $dotproduct->invokeArgs($this->associator, array($vector1, $vector2));

        $this->assertEquals($output, $expected);
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

        $output = $vectormodule->invokeArgs($this->associator, array($vector));

        $this->assertEquals($output, $expected, '', $delta);
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

        $output = $cosinesimilarity->invokeArgs($this->associator, array($vector1, $vector2));

        $this->assertEquals($output, $expected, '', $delta);
    }

}

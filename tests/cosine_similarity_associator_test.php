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

        $currentdata = array(array());
        $historicdata = array(array());
        $expected = array(array());

        $historicdata['1']['10'] = 54
        $historicdata['1']['11'] = 1
        $historicdata['1']['12'] = 35
        $historicdata['1']['13'] = 65
        $historicdata['2']['10'] = 145
        $historicdata['2']['11'] = 65
        $historicdata['2']['12'] = 46
        $historicdata['2']['13'] = 123
        $historicdata['3']['10'] = 6
        $historicdata['3']['11'] = 76
        $historicdata['3']['12'] = 45
        $historicdata['3']['13'] = 12
        $historicdata['4']['10'] = 65
        $historicdata['4']['11'] = 1254
        $historicdata['4']['12'] = 54
        $historicdata['4']['13'] = 54
        $historicdata['5']['10'] = 12
        $historicdata['5']['11'] = 76
        $historicdata['5']['12'] = 45
        $historicdata['5']['13'] = 78
        $historicdata['6']['10'] = 32
        $historicdata['6']['11'] = 489
        $historicdata['6']['12'] = 12
        $historicdata['6']['13'] = 37

        $currentdata['100']['10'] = 456
        $currentdata['100']['11'] = 71
        $currentdata['100']['12'] = 23
        $currentdata['100']['13'] = 47
        $currentdata['101']['10'] = 31
        $currentdata['101']['11'] = 217
        $currentdata['101']['12'] = 41
        $currentdata['101']['13'] = 34
        $currentdata['102']['10'] = 31
        $currentdata['102']['11'] = 87
        $currentdata['102']['12'] = 64
        $currentdata['102']['13'] = 12

        $ouput['100']['1'] = 0.6721
        $ouput['100']['2'] = 0.8102
        $ouput['100']['3'] = 0.2345
        $ouput['100']['4'] = 0.2096
        $ouput['100']['5'] = 0.2830
        $ouput['100']['6'] = 0.2248
        $ouput['101']['1'] = 0.2683
        $ouput['101']['2'] = 0.5305
        $ouput['101']['3'] = 0.9394
        $ouput['101']['4'] = 0.9803
        $ouput['101']['5'] = 0.7995
        $ouput['101']['6'] = 0.9816
        $ouput['102']['1'] = 0.4625
        $ouput['102']['2'] = 0.6254
        $ouput['102']['3'] = 0.9729
        $ouput['102']['4'] = 0.8105
        $ouput['102']['5'] = 0.8069
        $ouput['102']['6'] = 0.805

        $output = $this->associator->create_associations_matrix($currentdata, $historicdata);

        $this->markTestIncomplete('Method not implemented yet.');
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

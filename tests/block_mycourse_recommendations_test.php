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
 * Unit tests for database handling of mycourse_recommendations block.
 *
 * @package    block_mycourse_recommendations
 * @category   phpunit
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/moodleblock.class.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/block_mycourse_recommendations.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');

use block_mycourse_recommendations\database_helper;

/**
 * Test cases for block_mycourse_recommendations for block output.
 */
class block_mycourse_recommendations_testcase extends advanced_testcase {

    protected $block;
    protected $db;

    protected function setUp() {
        parent::setUp();
        $this->block = new block_mycourse_recommendations();
        $this->db = new database_helper();

        $this->block->init();
        // The $instance attribute is block_base's attribute, with the instance of the block implementation; the block
        // we are testing. In this case, as we are testing the function "unitarily", we have to assign some irrelevant
        // value, to make the function not return an empty string.
        $this->block->instance = 'something';
    }

    protected function tearDown() {
        $this->block = null;
        $this->db = null;
        parent::tearDown();
    }

    /**
     * Creates a number of courses with the given attributes.
     *
     * @param array $attributes The attributes of the course (fullname, startdate, etc.).
     * @param int $number The number of courses to create for the given previous parameters.
     * @return array The created courses.
     */
    protected function create_courses($attributes, $number) {
        $courses = array();

        for ($index = 0; $index < $number; $index++) {
            $courses[$index] = $this->getDataGenerator()->create_course($attributes);
        }

        return $courses;
    }

    public function test_get_content_firstinstance_nopersonalizable() {
        global $COURSE;

        $this->resetAfterTest();
        $this->setAdminUser();

        // We create the course and we set the global variable $COURSE with it, in order to make the block to access it...
        $course = $this->create_courses(array(), 1)[0];
        $COURSE = $course;

        $expected = new stdClass();
        $expected->text = get_string('notpersonalizable', 'block_mycourse_recommendations');
        $expected->footer = '';
        $actual = $this->block->get_content();

        $this->assertEquals($expected, $actual);
    }

    public function test_get_content_nopersonalizable() {
        global $COURSE, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // We create the course and we set the global variable $COURSE with it, in order to make the block to access it...
        $course = $this->create_courses(array(), 1)[0];
        $COURSE = $course;

        // We set the course as not personalizable in plugin's table.
        $personalizable = 0;
        $sql = "INSERT INTO {block_mycourse_course_sel} (courseid, active, personalizable, year)
                VALUES(:v1, :v2, :v3, :v4)";
        $values = ['v1' => $course->id, 'v2' => 1, 'v3' => $personalizable, 'v4' => 2016];
        $DB->execute($sql, $values);

        $expected = new stdClass();
        $expected->text = get_string('notpersonalizable', 'block_mycourse_recommendations');
        $expected->footer = '';
        $actual = $this->block->get_content();

        $this->assertEquals($expected, $actual);
    }

    public function test_get_content_personalizable_inactive() {
        global $COURSE, $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // We create the course and we set the global variable $COURSE with it, in order to make the block to access it...
        $course = $this->create_courses(array(), 1)[0];
        $COURSE = $course;

        // We set the course as personalizable but inactive in plugin's table.
        $active = 0;
        $personalizable = 1;
        $sql = "INSERT INTO {block_mycourse_course_sel} (courseid, active, personalizable, year)
                VALUES(:v1, :v2, :v3, :v4)";
        $values = ['v1' => $course->id, 'v2' => $active, 'v3' => $personalizable, 'v4' => 2016];
        $DB->execute($sql, $values);

        $expected = new stdClass();
        $expected->text = get_string('inactive', 'block_mycourse_recommendations');
        $expected->footer = '';
        $actual = $this->block->get_content();

        $this->assertEquals($expected, $actual);
    }
}
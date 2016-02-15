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
 * Unit tests for course filtering of mycourse_recommendations block.
 *
 * @package    block_mycourse_recommendations
 * @category   phpunit
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/course_filter/course_filter.php');

use block_mycourse_recommendations\database_helper;
use block_mycourse_recommendations\course_filter;

/**
 * Test cases for block_mycourse_recommendations for course filtering.
 */
class block_mycourse_recommendations_course_filter_testcase extends advanced_testcase {

    protected $db;
    protected $previouscourseattributes;
    protected $previouscourses;
    protected $previousresources;
    protected $currentyear;
    protected $currentcourseattributes;
    protected $currentcourse;

    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();

        $this->db = new database_helper();
        $this->previouscourseattributes = array('fullname' => 'Software Engineering II',
                                                'startdate' => strtotime('01-01-2009'));

        $this->currentyear = 2016;
        $this->currentcourseattributes = array('fullname' => 'Software Engineering II',
                                               'startdate' => strtotime("15-02-$this->currentyear"));

        $this->previouscourses = $this->create_courses($this->previouscourseattributes, 3);
        $this->previousresources = array();
        $this->previousresources[$this->previouscourses[0]->id]['mod_page'] = 2;
        $this->previousresources[$this->previouscourses[0]->id]['mod_url'] = 7;
        $this->previousresources[$this->previouscourses[0]->id]['mod_book'] = 3;
        $this->previousresources[$this->previouscourses[1]->id]['mod_resource'] = 4;
        $this->previousresources[$this->previouscourses[1]->id]['mod_page'] = 3;
        $this->previousresources[$this->previouscourses[1]->id]['mod_url'] = 10;
        $this->create_resources();

        $this->currentcourse = $this->create_courses($this->currentcourseattributes, 1);
    }

    protected function tearDown() {
        $this->db = null;
        parent::tearDown();
    }

    /**
     * Creates a number of courses with the given attributes.
     *
     * @param array $attibutes The attributes of the course (fullname, startdate, etc.).
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
    
    /**
     * Creates resources.
     *
     * @param array $resources number of resources of a type for a course.
     */
    protected function create_resources($resources) {
        foreach ($resources as $courseid => $course) {
            foreach ($course as $resourcetype => $number) {
                $generator = $this->getDataGenerator()->get_plugin_generator($resourcetype);

                for ($index = 0; $index < $number; $index++) {
                    $generator->create_instance(array('course' => $courseid));
                }
            }
        }
    }

    public function test_meets_minimum_previous_courses() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $currentcourseid = $this->currentcourse[0]->id;
        $currentyear = $this->currentyear;

        $expected = true;

        $actual = course_filter::meets_minimum_previous_courses($currentcourseid, $currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }

    public function test_meets_minimum_resources() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $currentcourseid = $this->currentcourse[0]->id;
        $currentyear = $this->currentyear;

        $expected = true;

        $actual = course_filter::meets_minimum_resources($currentcourseid, $currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }
}

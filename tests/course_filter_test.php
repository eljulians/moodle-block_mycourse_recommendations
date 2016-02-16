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

        $this->currentcourse = $this->create_courses($this->currentcourseattributes, 1);
    }

    protected function tearDown() {
        $this->db = null;
        $this->previouscourseattributes = null;
        $this->previouscourses = null;
        $this->currentyear = null;
        $this->currentcourseattributes = null;
        $this->currentcourse = null;
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

    /**
     * Creates n number of students (roleid = 5) for the given course.
     *
     * @param int $courseid The course to enrol the student in.
     * @param int $number The number of students to create.
     */
    protected function create_and_enrol_students($courseid, $number) {
        for ($index = 0; $index < $number; $index++) {
            $newuser = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($newuser->id, $courseid, 5); // The student role id.
        }
    }

    // Tests for "meets_minimum_previous_courses" function start here.

    /**
     * Tests that the function returns false when the number of previous courses is lower than the minimum defined,
     * i.e., the input is OUTSIDE the lower limit of the input domain.
     */
    public function test_meets_minimum_previous_courses_below() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The course won't meet the minimum previous courses, so, we expect a false from the function.
        $expected = false;

        // We stablish a value that is outside, in the lower limit, of the input domain.
        $numberofcoursestocreate = course_filter::MINIMUM_PREVIOUS_COURSES - 1;

        // We create those courses, with the attributes defined in setUp.
        $this->create_courses($this->previouscourseattributes, $numberofcoursestocreate);

        // We get the actual value and we do the assertion.
        $currentcourseid = $this->currentcourse[0]->id;        
        $actual = course_filter::meets_minimum_previous_courses($currentcourseid, $this->currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the function returns true when the number of previous courses is the same as the minimum defined,
     * i.e., the input is EQUAL to the lower limit (the unique limit, in this case) of the input domain.
     */
    public function test_meets_minimum_previous_courses_equal() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The course will meet the minimum previous courses, so, we expect a true from the function.
        $expected = true;

        // We stablish the value that defines the lower limit (and the unique one) of the input domain.
        $numberofcoursestocreate = course_filter::MINIMUM_PREVIOUS_COURSES;

        // We create those courses, with the attributes defined in setUp.
        $this->create_courses($this->previouscourseattributes, $numberofcoursestocreate);

        // We get the actual value and we do the assertion.
        $currentcourseid = $this->currentcourse[0]->id;
        $actual = course_filter::meets_minimum_previous_courses($currentcourseid, $this->currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the function returns true when the number of previous courses is higher than the minumimum defined,
     * i.e., the input is ABOVE the lower limit of the input domain. In this case, since the input domain only has a limit,
     * the lower one, the input is also INSIDE.
     */
    public function test_meets_minimum_previous_courses_above() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The course will meet the minimum previous courses, so, we expect a true from the function.
        $expected = true;

        // We stablish a value that is inside the input domain.
        $numberofcoursestocreate = course_filter::MINIMUM_PREVIOUS_COURSES + 1;

        // We create those courses, with the attributes defined in setUp.
        $this->create_courses($this->previouscourseattributes, $numberofcoursestocreate);

        // We get the actual value and we do the assertion.
        $currentcourseid = $this->currentcourse[0]->id;
        $actual = course_filter::meets_minimum_previous_courses($currentcourseid, $this->currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }

    // Tests for "meets_minimum_resources" start here.

    /**
     * Tests that the function returns false when the number of previous resources is lower than the minimum defined,
     * i.e., the input is OUTSIDE the lower limit of the input domain.
     */
    public function test_meets_minimum_resources_below() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The course won't meet the minimum previous resources, so, we expect a true from the function.
        $expected = false;

        // We stablish a value that is outside, in the lower limit, of the input domain.
        $numberofresources = course_filter::MINIMUM_PREVIOUS_RESOURCES - 1;

        // We create those courses, with the attributes defined in setUp.
        $numberofcoursestocreate = course_filter::MINIMUM_PREVIOUS_COURSES;
        $previouscourses = $this->create_courses($this->previouscourseattributes, $numberofcoursestocreate);

        // We divide the resources to create in, let's say, 3 types of resources, to have a bit of variation, and we create them.
        $previousresources = array();
        $previousresources[$previouscourses[0]->id]['mod_page'] = $numberofresources / 3;
        $previousresources[$previouscourses[0]->id]['mod_url'] = $numberofresources / 3;
        $previousresources[$previouscourses[0]->id]['mod_book'] = $numberofresources / 3;
        $this->create_resources($previousresources);

        // We get the actual value and we do the assertion.
        $currentcourseid = $this->currentcourse[0]->id;
        $actual = course_filter::meets_minimum_resources($currentcourseid, $this->currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the function returns true when the number of previous resources is the same as the minimum defined,
     * i.e., the input is EQUAL to the lower limit (the unique limit, in this case) of the input domain.
     */
    public function test_meets_minimum_resources_equal() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The course will meet the minimum previous resources, so, we expect a true from the function.
        $expected = true;

        // We stablish the value that defines the lower limit (and the unique one) of the input domain.
        $numberofresources = course_filter::MINIMUM_PREVIOUS_RESOURCES;

        // We have to creates some courses before creating resources, with the attributes defined in setUp.
        $numberofcoursestocreate = course_filter::MINIMUM_PREVIOUS_COURSES;
        $previouscourses = $this->create_courses($this->previouscourseattributes, $numberofcoursestocreate);

        // We divide the resources to create in, let's say, 2 types of resources, to have a bit of variation, and we create them.
        $previousresources = array();
        $previousresources[$previouscourses[0]->id]['mod_page'] = $numberofresources / 2;
        $previousresources[$previouscourses[0]->id]['mod_url'] = $numberofresources / 2;
        $this->create_resources($previousresources);

        // We get the actual value and we do the assertion.
        $currentcourseid = $this->currentcourse[0]->id;
        $actual = course_filter::meets_minimum_resources($currentcourseid, $this->currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the function returns true when the number of previous resources is higher than the minumimum defined,
     * i.e., the input is ABOVE the lower limit of the input domain. In this case, since the input domain only has a limit,
     * the lower one, the input is also INSIDE.
     */
    public function test_meets_minimum_resources_above() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The course will meet the minimum previous resources, so, we expect a true from the function.
        $expected = true;

        // We stablish a value that is inside the input domain.
        $numberofresources = course_filter::MINIMUM_PREVIOUS_RESOURCES + 1;

        // We have to creates some courses before creating resources, with the attributes defined in setUp.
        $numberofcoursestocreate = course_filter::MINIMUM_PREVIOUS_COURSES;
        $previouscourses = $this->create_courses($this->previouscourseattributes, $numberofcoursestocreate);

        // We divide the resources to create in, let's say, 3 types of resources, to have a bit of variation.
        $previousresources = array();
        $previousresources[$previouscourses[0]->id]['mod_page'] = $numberofresources / 3;
        $previousresources[$previouscourses[0]->id]['mod_url'] = $numberofresources / 3;
        $previousresources[$previouscourses[0]->id]['mod_book'] = $numberofresources / 3;
        $this->create_resources($previousresources);

        // We get the actual value and we do the assertion.
        $currentcourseid = $this->currentcourse[0]->id;
        $actual = course_filter::meets_minimum_resources($currentcourseid, $this->currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }

    // Tests for "meets_minimum_previous_students" start here.

    /**
     * Tests that the function returns false when the number of previous students is lower than the minimum defined,
     * i.e., the input is OUTSIDE the lower limit of the input domain.
     */
    public function test_meets_minimum_previous_students_below() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The course won't meet the minimum previous students, so, we expect a true from the function.
        $expected = false;

        // We stablish a value that is outside, in the lower limit, of the input domain.
        $numberofstudents = course_filter::MINIMUM_PREVIOUS_STUDENTS - 1;

        // We have to creates some courses before creating students, with the attributes defined in setUp.
        $numberofcoursestocreate = course_filter::MINIMUM_PREVIOUS_COURSES;
        $previouscourses = $this->create_courses($this->previouscourseattributes, $numberofcoursestocreate);

        // We create and enrol the students in one course...
        $this->create_and_enrol_students($previouscourses[0]->id, $numberofstudents);

        // We get the actual value and we do the assertion.
        $currentcourseid = $this->currentcourse[0]->id;
        $actual = course_filter::meets_minimum_previous_students($currentcourseid, $this->currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the function returns true when the number of previous students is the same as the minimum defined,
     * i.e., the input is EQUAL to the lower limit (the unique limit, in this case) of the input domain.
     */
    public function test_meets_minimum_previous_students_equal() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The course will meet the minimum previous students, so, we expect a true from the function.
        $expected = true;

        // We stablish the value that defines the lower limit (and the unique one) of the input domain.
        $numberofstudents = course_filter::MINIMUM_PREVIOUS_STUDENTS;

        // We have to creates some courses before creating students, with the attributes defined in setUp.
        $numberofcoursestocreate = course_filter::MINIMUM_PREVIOUS_COURSES;
        $previouscourses = $this->create_courses($this->previouscourseattributes, $numberofcoursestocreate);


        // We create and enrol the students in one course...
        $this->create_and_enrol_students($previouscourses[0]->id, $numberofstudents);

        // We get the actual value and we do the assertion.
        $currentcourseid = $this->currentcourse[0]->id;
        $actual = course_filter::meets_minimum_previous_students($currentcourseid, $this->currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Tests that the function returns true when the number of previous students is higher than the minumimum defined,
     * i.e., the input is ABOVE the lower limit of the input domain. In this case, since the input domain only has a limit,
     * the lower one, the input is also INSIDE.
     */
    public function test_meets_minimum_previous_students_above() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // The course will meet the minimum previous students, so, we expect a true from the function.
        $expected = true;

        // We stablish a value that is inside the input domain.
        $numberofstudents = course_filter::MINIMUM_PREVIOUS_STUDENTS + 1;

        // We have to creates some courses before creating students, with the attributes defined in setUp.
        $numberofcoursestocreate = course_filter::MINIMUM_PREVIOUS_COURSES;
        $previouscourses = $this->create_courses($this->previouscourseattributes, $numberofcoursestocreate);

        // We create and enrol the students in one course...
        $this->create_and_enrol_students($previouscourses[0]->id, $numberofstudents);

        // We get the actual value and we do the assertion.
        $currentcourseid = $this->currentcourse[0]->id;
        $actual = course_filter::meets_minimum_previous_students($currentcourseid, $this->currentyear, $this->db);

        $this->assertEquals($expected, $actual);
    }

}

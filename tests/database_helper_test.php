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
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php'); // Include the code to test.
use block_mycourse_recommendations\database_helper;

/**
 * Test cases for block_mycourse_recommendations for database handling.
 */
class block_mycourse_recommendations_testcase extends advanced_testcase {

    protected $databasehelper;
    protected $course;
    protected $users;
    protected $resource;

    /**
     * Set up the test environment.
     */
    protected function setUp() {
        parent::setUp();
        $this->setAdminUser();

        $this->users = array();
        $this->databasehelper = new database_helper();
        $this->course = $this->getDataGenerator()->create_course();

        for ($count = 0; $count < 10; $count++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $this->course->id);
            array_push($this->users, $user);
        }

        $pagegenerator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $this->resource = $pagegenerator->create_instance(array('course' => $this->course->id));
    }

    protected function tearDown() {
        $this->databasehelper = null;
        $this->course = null;
        $this->users = null;
        $this->resource = null;
        parent::tearDown();
    }

    protected static function get_method($name) {
        $class = new \ReflectionClass('\block_mycourse_recommendations\database_helper');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    /**
     * Tests that function inserts associations properly, with the expected behaviour.
     */
    public function test_insert_associations() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Inputs for the function.
        $number = 3;
        $currentusersids = array(1, 2, 3);
        $currentcourseid = 1;
        $historicuserids = array(100, 200, 300);
        $historiccourseid = 2;
        $week = 1;

        $this->databasehelper->insert_associations($number, $currentusersids, $currentcourseid, $historicuserids,
            $historiccourseid, $week);
    }

    /**
     * Tests that function throws exception if receives any array of different length.
     */
    public function test_insert_associations_exception() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Inputs for the function.
        $number = 4;
        $currentusersids = array(1, 2, 3, 4);
        $currentcourseid = 1;
        $historicuserids = array(100, 200, 300);
        $historiccourseid = 2;
        $week = 1;

        try {
            $this->databasehelper->insert_associations($number, $currentusersids, $currentcourseid, $historicuserids,
                $historiccourseid, $week);
            $this->fail('Exception should have been thrown in previous sentence.');
        } catch (Exception $e) {
            $this->assertTrue(true); // Silly workaround to pass the codecheker tests...
        }
    }

    /**
     * Tests that function inserts recommendations properly, with the expected behaviour.
     */
    public function test_insert_recommendations() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Inputs for the function.
        $number = 3;
        $associationsids = array(1, 2, 3);
        $resourcesids = array(4, 5, 6);
        $priorities = array(6, 7, 8);

        $this->databasehelper->insert_recommendations($number, $associationsids, $resourcesids, $priorities);
    }

    /**
     * Tests that function throws exception if receives any array of different length.
     */
    public function test_insert_recommendations_exception() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Inputs for the function.
        $number = 4;
        $associationsids = array(1, 2, 3);
        $resourcesids = array(4, 5, 6);
        $priorities = array(6, 7, 8, 9);

        try {
            $this->databasehelper->insert_recommendations($number, $associationsids, $resourcesids, $priorities);
            $this->fail('Exception should have been thrown in previous sentence.');
        } catch (Exception $e) {
            $this->assertTrue(true); // Silly workaround to pass the codecheker tests...
        }
    }

    /**
     * Tests that the function retrieves correctly students' ids belonging to the given course.
     */
    public function test_get_students_from_course() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $studentroleid = 5;

        $courses = array();
        $users = array();

        $courses[0] = $this->getDataGenerator()->create_course();
        $courses[1] = $this->getDataGenerator()->create_course();

        $users[0] = $this->getDataGenerator()->create_user();
        $users[1] = $this->getDataGenerator()->create_user();
        $users[2] = $this->getDataGenerator()->create_user();
        $users[3] = $this->getDataGenerator()->create_user();

        $this->getDataGenerator()->enrol_user($courses[0]->id, $users[0]->id, $studentroleid);
        $this->getDataGenerator()->enrol_user($courses[0]->id, $users[1]->id, $studentroleid);

        $this->getDataGenerator()->enrol_user($courses[1]->id, $users[2]->id, $studentroleid);
        $this->getDataGenerator()->enrol_user($courses[1]->id, $users[3]->id, $studentroleid);

        $expected = array();
        $expected[0] = array($users[0], $users[1]);
        $expected[1] = array($users[2], $users[3]);

        $output = array();
        $output[0] = $this->databasehelper->get_students_from_course($courses[0]->id);
        $output[1] = $this->databasehelper->get_students_from_course($courses[1]->id);

        // Fails when trying to assert the equality of the arrays, so we iterate it to assert
        // the members.
        for ($row = 0; $row < count($output); $row++) {
            for ($column = 0; $column < count($output[$row]); $column++) {
                $this->assertEquals($output[$row][$column], $expected[$row][$column]->id);
            }
        }
    }

    /**
     * Tests that the function inserts correctly the selected users in the corresponding table,
     * first, inserting them, and them, querying the database and comparing the results with the
     * inserted values.
     */
    public function test_insert_selections() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $course = 50;
        $year = 2016;
        $users = array();

        $users[0] = 100;
        $users[1] = 101;
        $users[2] = 102;

        $this->databasehelper->insert_selections($users, $course, $year);

        $sql = 'SELECT userid, courseid, year
                FROM {block_mycourse_user_sel}
                ORDER BY userid ASC';
        $records = $DB->get_records_sql($sql);

        // The output array is indexed by user id.
        $index = 0;
        foreach ($records as $output) {
            $this->assertEquals($output->userid, $users[$index]);
            $this->assertEquals($output->courseid, $course);
            $this->assertEquals($output->year, $year);

            $index++;
        }
    }

    /**
     * Tests that the function queries properly the ids of the previous teachings of a course, which are currently found
     * looking at the same 'fullname' field.
     */
    public function test_find_course_previous_teachings_ids() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Important parameters for the test: the fullname of the course; the current year, and a date with a lower year.
        $fullname = 'Software Engineering';
        $currentyear = 2016;
        $previouscoursestimestamp = strtotime('01-01-2009');

        // We create the current course...
        $currenttimestamp = strtotime("15-02-$currentyear");
        $currentcourse = $this->getDataGenerator()->create_course(array('fullname' => $fullname,
                                                                        'startdate' => $currenttimestamp));

        // We create the previous courses...
        $previouscourses = array();
        $previouscourses[0] = $this->getDataGenerator()->create_course(array('fullname' => $fullname,
                                                                             'startdate' => $previouscoursestimestamp));
        $previouscourses[1] = $this->getDataGenerator()->create_course(array('fullname' => $fullname,
                                                                             'startdate' => $previouscoursestimestamp));
        $previouscourses[2] = $this->getDataGenerator()->create_course(array('fullname' => $fullname,
                                                                             'startdate' => $previouscoursestimestamp));

        $expected = array();
        foreach ($previouscourses as $expectedcourse) {
            array_push($expected, $expectedcourse->id);
        }

        // We get the method using reflection, and we invoke it.
        $findpreviousteachings = self::get_method('find_course_previous_teachings_ids');
        $output = $findpreviousteachings->invokeArgs($this->databasehelper, array($currentcourse->id, $currentyear));

        // The arrays must be ordered in order to consider them equals.
        sort($output);
        sort($expected);

        $this->assertEquals($output, $expected);
    }
}

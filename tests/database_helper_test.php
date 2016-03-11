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
class block_mycourse_database_helper_testcase extends advanced_testcase {

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

    protected function insert_previous_resources_in_historic_data($resources, $courseid, $uniquekey = null) {
        global $DB;

        $i = 0;
        $uq = 0;

        foreach ($resources as $index => $resource) {
            $record = new stdClass();

            // Dirty, very dirty workaround for generating unique combinations for the records.
            if ($uniquekey !== null) {
                $uq = $uniquekey * 100 + $i;
            }
            $record->courseid = $courseid;
            $record->resourcename = 'some resource'; // Whatever.
            $record->resourcetype = 'page'; // Whatever.
            $record->resourceid = $uq; // The value is irrelevant, but it must be unique.
            $record->userid = $uq; // Whatever.
            $record->views = $uq; // Whatever.
            $record->timecreated = $uq; // Whatever.

            $DB->insert_record('block_mycourse_hist_data', $record);
            $i++;
        }
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

        $actual = array();
        $actual[0] = $this->databasehelper->get_students_from_course($courses[0]->id);
        $actual[1] = $this->databasehelper->get_students_from_course($courses[1]->id);

        // Fails when trying to assert the equality of the arrays, so we iterate it to assert
        // the members.
        for ($row = 0; $row < count($actual); $row++) {
            for ($column = 0; $column < count($actual[$row]); $column++) {
                $this->assertEquals($expected[$row][$column]->id, $actual[$row][$column]);
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
        foreach ($records as $actual) {
            $this->assertEquals($users[$index], $actual->userid);
            $this->assertEquals($course, $actual->courseid);
            $this->assertEquals($year, $actual->year);

            $index++;
        }
    }

    /**
     * Creates a number of courses with the given fullname and startdate. There is a specific function for this task
     * because it is used several times in this tests.
     *
     * @param string $fullname The fullname of the course.
     * @param int $startdate The UNIX timestamp of the start of the course.
     * @param int $number The number of courses to create for the given previous parameters.
     * @return array The ids of the created courses.
     */
    private function create_course($fullname, $startdate, $number) {
        $coursesids = array();

        for ($index = 0; $index < $number; $index++) {
            $coursesids[$index] = $this->getDataGenerator()->create_course(array('fullname' => $fullname,
                                                                                'startdate' => $startdate));
        }

        return $coursesids;
    }

    protected function insert_previous_courses_in_historic_data($previouscourses) {
        global $DB;

        foreach ($previouscourses as $index => $previouscourse) {
            $record = new stdClass();
            $record->fullname = $previouscourse->fullname;
            $record->shortname = $previouscourse->shortname;
            $record->startdate = $previouscourse->startdate + $index; // It must be unique.
            $record->idnumber = $previouscourse->idnumber;
            $record->category = $previouscourse->category;

            $DB->insert_record('block_mycourse_hist_course', $record);
        }

        $createdids = array();

        $records = $DB->get_records('block_mycourse_hist_course');

        foreach ($records as $record) {
            array_push($createdids, $record->id);
        }

        return $createdids;
    }

    protected function insert_previous_users_in_historic_data($users, $courseid) {
        global $DB;

        foreach ($users as $user) {
            $sql = 'INSERT INTO {block_mycourse_hist_enrol} (userid, courseid)
                    VALUES (:v1, :v2)';
            $values = ['v1' => $user->id, 'v2' => $courseid];

            $DB->execute($sql, $values);
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
        $currentcourse = array();
        $currentcourse = $this->create_course($fullname, $currenttimestamp, 1);

        // We create the previous courses...
        $previouscourses = $this->create_course($fullname, $previouscoursestimestamp, 3);
        $previouscourses = $this->insert_previous_courses_in_historic_data($previouscourses);

        $expected = array();
        foreach ($previouscourses as $expectedcourse) {
            array_push($expected, $expectedcourse);
        }

        // We get the method using reflection, and we invoke it.
        $findpreviousteachings = self::get_method('find_course_previous_teachings_ids_historic_tables');
        $actual = $findpreviousteachings->invokeArgs($this->databasehelper, array($currentcourse[0]->id, $currentyear));

        // The arrays must be ordered in order to consider them equals.
        sort($actual);
        sort($expected);

        $this->assertEquals($expected, $actual);
    }

    public function test_get_previous_courses_students_number() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $studentroleid = 5;

        // Important parameters for the test: the fullname of the course; the current year, and a date with a lower year.
        $fullname = 'Software Engineering';
        $currentyear = 2016;
        $previouscoursestimestamp = strtotime('01-01-2009');

        // We create the current course...
        $currenttimestamp = strtotime("15-02-$currentyear");
        $currentcourse = array();
        $currentcourse = $this->create_course($fullname, $currenttimestamp, 1);

        // We create the previous courses...
        $previouscourses = $this->create_course($fullname, $previouscoursestimestamp, 3);
        $previouscourses = $this->insert_previous_courses_in_historic_data($previouscourses);

        $previoususers = array();
        $previoususers[$previouscourses[0]] = 5;
        $previoususers[$previouscourses[1]] = 15;
        $previoususers[$previouscourses[2]] = 23;
        $expected = 0;

        $users = array();
        foreach ($previoususers as $courseid => $usernumber) {
            for ($index = 0; $index < $usernumber; $index++) {
                $newuser = $this->getDataGenerator()->create_user();
                $this->getDataGenerator()->enrol_user($newuser->id, $courseid, $studentroleid);

                array_push($users, $newuser);
                $expected++;
            }
        }
        $this->insert_previous_users_in_historic_data($users, $previouscourses[0]);

        $actual = $this->databasehelper->get_previous_courses_students_number_historic_tables($currentcourse[0]->id, $currentyear);

        $this->assertEquals($expected, $actual);
    }

    /**
     * Creates n resources of the given type, for the given course. Having a separated function for this makes
     * the tests more clear.
     *
     * @param int $courseid The id of the course.
     * @param string $resourcetype The type of resource to create.
     * @param int $number The number of resources to create.
     */
    private function create_resource($courseid, $resourcetype, $number) {
        $resources = array();
        $generator = $this->getDataGenerator()->get_plugin_generator($resourcetype);

        for ($index = 0; $index < $number; $index++) {
            $resource = $generator->create_instance(array('course' => $courseid));
            array_push($resources, $resource);
        }

        return $resources;
    }

    public function test_get_previous_courses_resources_number() {
        $this->resetAfterTest();
        $this->setAdminUser();

        // Important parameters for the test: the fullname of the course; the current year, and a date with a lower year.
        $fullname = 'Software Engineering';
        $currentyear = 2016;
        $previouscoursestimestamp = strtotime('01-01-2009');

        // We create the current course...
        $currenttimestamp = strtotime("15-02-$currentyear");
        $currentcourse = array();
        $currentcourse = $this->create_course($fullname, $currenttimestamp, 1);

        // We create the previous courses...
        $previouscourses = $this->create_course($fullname, $previouscoursestimestamp, 2);
        $previouscoursesids = $this->insert_previous_courses_in_historic_data($previouscourses);

        // We create some resources...
        $previousresources = array();
        $previousresources[$previouscourses[0]->id]['mod_page'] = 2;
        $previousresources[$previouscourses[0]->id]['mod_url'] = 7;
        $previousresources[$previouscourses[0]->id]['mod_book'] = 3;
        $previousresources[$previouscourses[1]->id]['mod_resource'] = 4;
        $previousresources[$previouscourses[1]->id]['mod_page'] = 3;
        $previousresources[$previouscourses[1]->id]['mod_url'] = 10;

        $expected = 0;
        $coursesresources = array();
        foreach ($previousresources as $courseid => $course) {
            foreach ($course as $resource => $number) {
                $resources = $this->create_resource($courseid, $resource, $number);
                array_push($coursesresources, $resources);
                $expected += $number;
            }
        }

        for ($index = 0; $index < count($coursesresources); $index++) {
            $resourcesbytype = $coursesresources[$index];
            $this->insert_previous_resources_in_historic_data($resourcesbytype, $previouscoursesids[0], $index);
        }

        $actual = $this->databasehelper->get_previous_courses_resources_number_core_tables($currentcourse[0]->id, $currentyear);

        $this->assertEquals($expected, $actual);
    }

    public function test_get_associations() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $currentusersids = array(100, 101, 102, 103);
        $currentcourseid = 20;
        $historicuserids = array(1, 2, 3, 4, 5);
        $historiccourseid = 10;
        $week = 1;

        $records = array();

        for ($index = 0; $index < 4; $index++) {
            $records[$index] = new stdClass();

            $records[$index]->current_userid = $currentusersids[$index];
            $records[$index]->current_courseid = $currentcourseid;
            $records[$index]->historic_userid = $historicuserids[$index];
            $records[$index]->historic_courseid = $historiccourseid;
            $records[$index]->week = $week;
        }

        $DB->insert_records('block_mycourse_assoc', $records);

        $actuals = $this->databasehelper->get_associations($currentcourseid, $week);

        foreach ($actuals as $index => $record) {
            unset($record->id);
            $actuals[$index] = $record;
        }

        $actuals = array_values($actuals);

        $expecteds = array();
        for ($index = 0; $index < 4; $index++) {
            $expecteds[$index] = new stdClass();

            $expecteds[$index]->current_userid = intval($currentusersids[$index]);
            $expecteds[$index]->historic_courseid = intval($historiccourseid);
            $expecteds[$index]->historic_userid = intval($historicuserids[$index]);
        }

        foreach ($actuals as $index => $actual) {
            $this->assertEquals($expecteds[$index], $actual);
        }
    }

    public function test_get_recommendations() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $currentcourseid = 20;
        $week = 1;

        // We create associations...
        $record = new stdClass();
        $record->current_userid = 100;
        $record->current_courseid = $currentcourseid;
        $record->historic_userid = 1;
        $record->historic_courseid = 10;
        $record->week = $week;

        $DB->insert_record('block_mycourse_assoc', $record);

        $associations = $this->databasehelper->get_associations($currentcourseid, $week);
        $associations = array_values($associations);

        $resourcesids = array(1000, 1001, 1002, 1003);
        $priorities = array(0, 1, 2, 3);

        $records = array();

        for ($index = 0; $index < 4; $index++) {
            $records[$index] = new stdClass();

            $records[$index]->associationid = $associations[0]->id;
            $records[$index]->resourceid = $resourcesids[$index];
            $records[$index]->priority = $priorities[$index];
        }

        $DB->insert_records('block_mycourse_recs', $records);

        $actuals = $this->databasehelper->get_recommendations($currentcourseid, $record->current_userid, $week);

        // If no recommendations is retrieved, something is wrong.
        $this->assertFalse(empty($actuals));

        foreach ($actuals as $index => $record) {
            unset($record->id);
            $actuals[$index] = $record;
        }

        $actuals = array_values($actuals);

        $expecteds = array();
        for ($index = 0; $index < 4; $index++) {
            $expecteds[$index] = new stdClass();

            $expecteds[$index]->resourceid = intval($resourcesids[$index]);
            $expecteds[$index]->priority = intval($priorities[$index]);
        }

        foreach ($actuals as $index => $actual) {
            $this->assertEquals($expecteds[$index], $actual);
        }
    }

    public function test_get_selected_active_courses() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $courses = array();
        $courses[0] = new stdClass();
        $courses[0]->courseid = 100;
        $courses[0]->active = 1;
        $courses[0]->year = 2015;
        $courses[0]->personalizable = true;

        $courses[1] = new stdClass();
        $courses[1]->courseid = 200;
        $courses[1]->active = 0;
        $courses[1]->year = 2017;
        $courses[1]->personalizable = true;

        $courses[2] = new stdClass();
        $courses[2]->courseid = 101;
        $courses[2]->active = 1;
        $courses[2]->year = 2015;
        $courses[2]->personalizable = true;

        foreach ($courses as $course) {
            $DB->execute("INSERT INTO {block_mycourse_course_sel} (courseid, active, year, personalizable)
                          VALUES($course->courseid, $course->active, $course->year, $course->personalizable)");
        }

        $actuals = $this->databasehelper->get_selected_active_courses();
        $actuals = array_values($actuals);

        // If the function output returns an empty array, something is wrong.
        $this->assertFalse(empty($actuals));

        // The expected values will be the same as input, but without the course that is not active.
        $expecteds = $courses;
        unset($expecteds[1]);
        $expecteds = array_values($expecteds);

        // We sort the objects to have them in the same order, to compare them in a loop.
        usort($expecteds, function($a, $b){
            return strcmp($a->courseid, $b->courseid);
        });
        usort($actuals, function($a, $b){
            return strcmp($a->courseid, $b->courseid);
        });

        foreach ($actuals as $index => $actual) {
            $this->assertEquals($expecteds[$index], $actual);
        }
    }


    public function test_set_course_inactive() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $courses = array();
        $courses[0] = new stdClass();
        $courses[0]->courseid = 100;
        $courses[0]->active = 1;
        $courses[0]->year = 2015;
        $courses[0]->personalizable = true;

        $courses[2] = new stdClass();
        $courses[2]->courseid = 101;
        $courses[2]->active = 1;
        $courses[2]->year = 2015;
        $courses[2]->personalizable = true;

        $courses[1] = new stdClass();
        $courses[1]->courseid = 200;
        $courses[1]->active = 1;
        $courses[1]->year = 2017;
        $courses[1]->personalizable = true;

        foreach ($courses as $course) {
            $DB->execute("INSERT INTO {block_mycourse_course_sel} (courseid, active, year, personalizable)
                          VALUES($course->courseid, $course->active, $course->year, $course->personalizable)");
        }

        $this->databasehelper->set_course_inactive($courses[1]->courseid);

        $actuals = $DB->get_records_sql("SELECT courseid, active, year, personalizable
                                         FROM   {block_mycourse_course_sel} sel
                                         WHERE  sel.active = 1");
        $actuals = array_values($actuals);

        // If the function output returns an empty array, something is wrong.
        $this->assertFalse(empty($actuals));

        // The expected values will be the same as input, but without the course that is not active.
        $expecteds = $courses;
        unset($expecteds[1]);
        $expecteds = array_values($expecteds);

        // We sort the objects to have them in the same order, to compare them in a loop.
        usort($expecteds, function($a, $b){
            return strcmp($a->courseid, $b->courseid);
        });
        usort($actuals, function($a, $b){
            return strcmp($a->courseid, $b->courseid);
        });

        foreach ($actuals as $index => $actual) {
            $this->assertEquals($expecteds[$index], $actual);
        }
    }

    public function test_set_courses_of_year_inactive() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $courses = array();
        $courses[0] = new stdClass();
        $courses[0]->courseid = 100;
        $courses[0]->active = 1;
        $courses[0]->year = 2015;
        $courses[0]->personalizable = true;

        $courses[1] = new stdClass();
        $courses[1]->courseid = 200;
        $courses[1]->active = 1;
        $courses[1]->year = 2017;
        $courses[1]->personalizable = true;

        $courses[2] = new stdClass();
        $courses[2]->courseid = 101;
        $courses[2]->active = 1;
        $courses[2]->year = 2015;
        $courses[2]->personalizable = true;

        foreach ($courses as $course) {
            $DB->execute("INSERT INTO {block_mycourse_course_sel} (courseid, active, year, personalizable)
                          VALUES($course->courseid, $course->active, $course->year, $course->personalizable)");
        }

        $this->databasehelper->set_courses_of_year_inactive($courses[1]->year);

        $actuals = $DB->get_records_sql("SELECT courseid, active, year, personalizable
                                         FROM   {block_mycourse_course_sel} sel
                                         WHERE  sel.active = 1");
        $actuals = array_values($actuals);

        // If the function output returns an empty array, something is wrong.
        $this->assertFalse(empty($actuals));

        // The expected values will be the same as input, but without the course that is not active.
        $expecteds = $courses;
        unset($expecteds[1]);
        $expecteds = array_values($expecteds);

        // We sort the objects to have them in the same order, to compare them in a loop.
        usort($expecteds, function($a, $b){
            return strcmp($a->courseid, $b->courseid);
        });
        usort($actuals, function($a, $b){
            return strcmp($a->courseid, $b->courseid);
        });

        foreach ($actuals as $index => $actual) {
            $this->assertEquals($expecteds[$index], $actual);
        }
    }

    public function test_is_blocks_first_instance_true() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $courseid = 100;

        $actual = $this->databasehelper->is_blocks_first_instance($courseid);

        $this->assertTrue($actual);
    }

    public function test_is_blocks_first_instance_false() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $courseid = 100;

        $sql = "INSERT INTO {block_mycourse_course_sel} (courseid, year, active, personalizable) VALUES(:v1, :v2, :v3, :v4)";
        $values = ['v1' => $courseid, 'v2' => 2016, 'v3' => 1, 'v4' => 1];
        $DB->execute($sql, $values);

        $actual = $this->databasehelper->is_blocks_first_instance($courseid);

        $this->assertFalse($actual);
    }

    public function test_is_course_personalizable_true() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $courseid = 100;
        $personalizable = 1;

        $sql = "INSERT INTO {block_mycourse_course_sel} (courseid, year, active, personalizable) VALUES(:v1, :v2, :v3, :v4)";
        $values = ['v1' => $courseid, 'v2' => 2016, 'v3' => 1, 'v4' => $personalizable];
        $DB->execute($sql, $values);

        $actual = $this->databasehelper->is_course_personalizable($courseid);

        $this->assertTrue($actual);
    }

    public function test_is_course_personalizable_false() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $courseid = 100;
        $personalizable = 0;

        // First, having the course no row in the table, the function should also return false.
        $actual = $this->databasehelper->is_course_personalizable($courseid);

        $this->assertFalse($actual);

        $sql = "INSERT INTO {block_mycourse_course_sel} (courseid, year, active, personalizable) VALUES(:v1, :v2, :v3, :v4)";
        $values = ['v1' => $courseid, 'v2' => 2016, 'v3' => 1, 'v4' => $personalizable];
        $DB->execute($sql, $values);

        $actual = $this->databasehelper->is_course_personalizable($courseid);

        $this->assertFalse($actual);
    }

    public function test_insert_similarity() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $currentuserid = 100;
        $historicuserid = 1;
        $coefficient = 0.7515;
        $week = 10;

        $this->databasehelper->insert_similarity($currentuserid, $historicuserid, $coefficient, $week);

        $expected = new stdClass();
        $expected->current_userid = $currentuserid;
        $expected->historic_userid = $historicuserid;
        $expected->coefficient = $coefficient;
        $expected->week = $week;

        $actual = $DB->get_records('block_mycourse_similarities');

        $distincttoone = count($actual) !== 1;

        // If the record number is distinct to 1, something is wrong.
        $this->assertFalse($distincttoone);

        // We unset the id, since we cannot determine it.
        foreach ($actual as $actualaux) {
            unset($actualaux->id);
            $this->assertEquals($expected, $actualaux);
        }
    }

}

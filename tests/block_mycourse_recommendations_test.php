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
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/renderer/recommendations_renderer.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/course_filter/course_filter.php');

use block_mycourse_recommendations\database_helper;
use block_mycourse_recommendations\recommendations_renderer;
use block_mycourse_recommendations\course_filter;

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

    protected function insert_previous_courses_in_historic_data($previouscourses) {
        global $DB;

        foreach ($previouscourses as $previouscourse) {
            $record = new stdClass();
            $record->fullname = $previouscourse->fullname;
            $record->shortname = $previouscourse->shortname;
            $record->startdate = $previouscourse->startdate;
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

    /**
     * Creates n number of students (roleid = 5) for the given course.
     *
     * @param int $courseid The course to enrol the student in.
     * @param int $number The number of students to create.
     * @return array Created and enrolled users.
     */
    protected function create_and_enrol_students($courseid, $number) {
        $users = array();

        for ($index = 0; $index < $number; $index++) {
            $newuser = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($newuser->id, $courseid, 5); // The student role id.
            array_push($users, $newuser);
        }

        return $users;
    }

    /**
     * Creates resources.
     *
     * @param array $resources Number of resources of a type for a course.
     * @param array $resourcesnames The name of each resource.
     * @return array Created resources.
     */
    protected function create_resources($resources, $resourcesnames) {
        $createdresources = array();

        foreach ($resources as $courseid => $course) {
            foreach ($course as $resourcetype => $number) {
                $generator = $this->getDataGenerator()->get_plugin_generator($resourcetype);

                for ($index = 0; $index < $number; $index++) {
                    $resource = $generator->create_instance(array('course' => $courseid, 'name' => $resourcesnames[$index]));
                    array_push($createdresources, $resource);
                }
            }
        }

        return $createdresources;
    }

    protected function insert_previous_resources_in_historic_data($resources, $courseid) {
        global $DB;

        foreach ($resources as $index => $resource) {
            $record = new stdClass();

            $record->courseid = $courseid;
            $record->resourcename = (string)$index; // The value is irrelevant, but it must be unique.
            $record->resourcetype = 'page'; // Whatever.
            $record->userid = $index; // Whatever.
            $record->views = $index; // Whatever.
            $record->timecreated = time(); // Whatever.

            $DB->insert_record('block_mycourse_hist_data', $record);
        }
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

    public function test_get_content_personalizable_active_usernotselected() {
        global $COURSE, $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // We create the course and we set the global variable $COURSE with it, in order to make the block to access it...
        $course = $this->create_courses(array(), 1)[0];
        $COURSE = $course;

        // We create a user and we enrol into the course...
        $studentnumber = 1;
        $user = $this->create_and_enrol_students($course->id, $studentnumber)[0];
        $USER = $user;

        // We set the course as personalizable and active in plugin's table.
        $active = 1;
        $personalizable = 1;
        $sql = "INSERT INTO {block_mycourse_course_sel} (courseid, active, personalizable, year)
                VALUES(:v1, :v2, :v3, :v4)";
        $values = ['v1' => $course->id, 'v2' => $active, 'v3' => $personalizable, 'v4' => 2016];
        $DB->execute($sql, $values);

        $expected = new stdClass();
        $expected->text = get_string('usernotselected', 'block_mycourse_recommendations');
        $expected->footer = '';
        $actual = $this->block->get_content();

        $this->assertEquals($expected, $actual);
    }

    public function test_get_content_personalizable_active_userselected() {
        global $COURSE, $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        // We create the course and we set the global variable $COURSE with it, in order to make the block to access it...
        $course = $this->create_courses(array(), 1)[0];
        $COURSE = $course;

        // We calculate the week number, necessary to get the recommendations.
        $currentweek = intval(date('W', time()));

        // We create a user and we enrol into the course...
        $studentnumber = 1;
        $user = $this->create_and_enrol_students($course->id, $studentnumber)[0];
        $USER = $user;

        // We set the course as personalizable and active in plugin's table.
        $active = 1;
        $personalizable = 1;
        $sql = "INSERT INTO {block_mycourse_course_sel} (courseid, active, personalizable, year)
                VALUES(:v1, :v2, :v3, :v4)";
        $values = ['v1' => $course->id, 'v2' => $active, 'v3' => $personalizable, 'v4' => 2016];
        $DB->execute($sql, $values);

        // We set the user as selected in plugin's table.
        $sql = "INSERT INTO {block_mycourse_user_sel} (userid, courseid, year)
                VALUES (:v1, :v2, :v3)";
        $values = ['v1' => $user->id, 'v2' => $course->id, 'v3' => 2016];
        $DB->execute($sql, $values);

        // We create the resources that are going to be recommended.
        $resourcesnames = array('Cryptographic Tools', 'User Authentication', 'Intrusion Detection');
        $resources[$course->id]['mod_page'] = count($resourcesnames);
        $resources = $this->create_resources($resources, $resourcesnames);

        // We create an association, which is irrelevant, except for the week, which is necessary to query the recommendations.
        $record = new stdClass();
        $record->current_userid = $user->id;
        $record->current_courseid = $course->id;
        $record->historic_userid = 0; // Irrelevant for this case.
        $record->historic_courseid = 0; // Irrelevant for this case.
        $record->week = $currentweek;
        $associationid = $DB->insert_record('block_mycourse_assoc', $record);

        // We insert the created resources as recommended.
        $records = array();
        for ($index = 0; $index < count($resources); $index++) {
            $records[$index] = new stdClass();
            $records[$index]->associationid = $associationid;
            $records[$index]->resourceid = $resources[$index]->id;
            $records[$index]->priority = $index;
        }

        $DB->insert_records('block_mycourse_recs', $records);
        // We have to know the recommendations ids, because they are used to create the Moodle url, so we need it for the
        // assertion.
        $lastinsertedid = $DB->get_record_sql('SELECT MAX(id) as lastinserted from {block_mycourse_recs}')->lastinserted;
        $firstinsertedid = $lastinsertedid - count($records) + 1;

        // We create the expected block output.
        $expected = new stdClass();
        $expected->footer = '';
        $expected->text = '<ol>';

        $currentrecommendationid = $firstinsertedid;
        for ($index = 0; $index < recommendations_renderer::MAX_RECOMMENDATIONS; $index++) {
            $url = new \moodle_url('/blocks/mycourse_recommendations/redirect_to_resource.php',
                array('recommendationid' => $currentrecommendationid, 'modname' => 'page', 'moduleid' => $resources[$index]->cmid));
            $expected->text .= '<li>';
            $expected->text .= "<a href='$url' target='_blank'>";
            $expected->text .= $resources[$index]->name;
            $expected->text .= '</a>';
            $expected->text .= '</li>';

            $currentrecommendationid++;
        }
        $expected->text .= '</ol>';

        $actual = $this->block->get_content();

        $this->assertEquals($expected, $actual);
    }

    public function test_get_content_personalizable_firstinstance() {
        global $COURSE, $DB, $USER;

        $this->resetAfterTest();
        $this->setAdminUser();

        $coursesname = 'Software Engineering';
        $currentcoursestart = strtotime('05-01-2016');
        $previouscoursestart = strtotime('05-01-2015');

        // We create the course and we set the global variable $COURSE with it, in order to make the block to access it...
        $course = $this->create_courses(array('fullname' => $coursesname, 'startdate' => $currentcoursestart), 1)[0];
        $COURSE = $course;

        // We create the current users and we enrol into the course...
        $studentnumber = 4;
        $user = $this->create_and_enrol_students($course->id, $studentnumber)[0];
        $USER = $user;

        // We create the previous minimum courses...
        $previouscourses = $this->create_courses(array('fullname' => $coursesname, 'startdate' => $previouscoursestart),
                                                course_filter::MINIMUM_PREVIOUS_COURSES);
        $previouscourse = $previouscourses[0];

        // We create the previous minimum students...
        $users = $this->create_and_enrol_students($previouscourse->id, course_filter::MINIMUM_PREVIOUS_STUDENTS);

        // We create the previous minimum weeks...
        $resourcesnames = array();
        for ($index = 0; $index <= course_filter::MINIMUM_PREVIOUS_RESOURCES; $index++) {
            array_push($resourcesnames, 'whatever resource');
        }
        $resources[$previouscourse->id]['mod_page'] = count($resourcesnames);
        $resources = $this->create_resources($resources, $resourcesnames);

        $actual = $this->block->get_content();

        // We don't know if the user has been selected! So, the expected value will vary depending on if the user
        // we have kept is between the selected ones, or not.
        $selectedusers = $DB->get_records('block_mycourse_user_sel');
        $selectedusers = array_keys($selectedusers);

        $hasuserbeenselected = in_array($user->id, $selectedusers);

        $expected = new stdClass();
        $expected->footer = '';

        if ($hasuserbeenselected) {
            $expected->text = get_string('norecommendations', 'block_mycourse_recommendations');
        } else {
            $expected->text = get_string('usernotselected', 'block_mycourse_recommendations');
        }

        $this->assertEquals($expected, $actual);
    }

}
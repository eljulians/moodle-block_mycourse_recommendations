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
 * Unit tests for simple recommendations of mycourse_recommendations block.
 *
 * @package    block_mycourse_recommendations
 * @category   phpunit
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/recommendator/simple_recommendator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/associator/cosine_similarity_associator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/decimal_matrix.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/course_filter/course_filter.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/query_result.php');

use block_mycourse_recommendations\simple_recommendator;
use block_mycourse_recommendations\cosine_similarity_associator;
use block_mycourse_recommendations\decimal_matrix;
use block_mycourse_recommendations\course_filter;
use block_mycourse_recommendations\database_helper;
use block_mycourse_recommendations\query_result;

class block_mycourse_recommendations_simple_recommendator_testcase extends advanced_testcase {

    protected $recommendator;
    protected $previousyear;
    protected $previousstartdate;
    protected $previouscourseattributes;
    protected $previouscourses;

    protected $currentyear;
    protected $currentstartdate;
    protected $currentcourseattributes;
    protected $currentcourse;
    protected $dbhelper;

    public function setUp() {
        parent::setUp();
        $this->recommendator = new simple_recommendator(new cosine_similarity_associator(new decimal_matrix));

        $this->previousyear = 2015;
        $this->previousstartdate = strtotime("06-01-$this->previousyear");

        $this->previouscourseattributes = array('fullname' => 'Software Engineering II',
            'startdate' => $this->previousstartdate);

        $this->currentyear = 2016;
        $this->currentstartdate = strtotime("12-01-$this->currentyear");
        $this->currentcourseattributes = array('fullname' => 'Software Engineering II',
            'startdate' => $this->currentstartdate);

        $this->currentcourse = $this->create_courses($this->currentcourseattributes, 1);
        $this->dbhelper = new database_helper();
    }

    public function tearDown() {
        $this->recommendator = null;
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
     * Creates resources.
     *
     * @param array $resources number of resources of a type for a course.
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

    protected function create_and_enrol_students($courseid, $number, $previoususers = false) {
        global $DB;

        $users = array();

        if (!$previoususers) {
            for ($index = 0; $index < $number; $index++) {
                $newuser = $this->getDataGenerator()->create_user();
                $this->getDataGenerator()->enrol_user($newuser->id, $courseid, 5); // The student role id.

                array_push($users, $newuser);
            }
        } else {
            for ($index = 0; $index < $number; $index++) {
                $newuser = $this->getDataGenerator()->create_user();
                $this->getDataGenerator()->enrol_user($newuser->id, $courseid, 5); // The student role id.
                $sql = 'INSERT INTO {block_mycourse_hist_enrol} (userid, courseid, grade)
                        VALUES(:v1, :v2, :v3)';
                $values = ['v1' => $newuser->id, 'v2' => $courseid, 'v3' => 7];
                $DB->execute($sql, $values);
                array_push($users, $newuser);
            }
        }

        return $users;
    }

    protected function create_logview($userid, $courseid, $resourceid, $eventname, $component, $timestamp, $number) {
        global $DB;

        for ($index = 0; $index < $number; $index++) {
            $logview = new stdClass();

            $page = $DB->get_record('page', array('id' => $resourceid));
            $cm = get_coursemodule_from_instance('page', $page->id, $page->course, false, MUST_EXIST);
            $context = context_module::instance($cm->id);

            $logview->eventname = $eventname;
            $logview->component = $component;
            $logview->action = 'viewed';
            $logview->target = 'course_module';
            $logview->objecttable = str_replace('mod_', '', $component);
            $logview->contextlevel = 50;
            $logview->userid = $userid;
            $logview->courseid = $courseid;
            $logview->edulevel = 2;
            $logview->contextid = $context->id;
            $logview->contextinstanceid = $context->instanceid;
            $logview->timecreated = $timestamp;

            $DB->insert_record('logstore_standard_log', $logview);
        }
    }

    protected function create_previous_course_logview($userid, $courseid, $resourcename, $resourcetype, $views,
                                                      $timecreated, $uniquevalue) {
        global $DB;

        $logview = new stdClass();

        $logview->courseid = $courseid;
        $logview->userid = $userid;
        $logview->resourcename = $resourcename;
        $logview->resourcetype = $resourcetype;
        $logview->resourceid = $uniquevalue;
        $logview->views = $views;
        $logview->timecreated = $timecreated;

        $DB->insert_record('block_mycourse_hist_data', $logview);
    }

    public function test_create_associations() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $eventname = '\\mod_page\\event\\course_module_viewed';
        $component = 'mod_page';

        $resourcesnames = array('Page 1', 'Page 2', 'Page 3');
        // We have to creates a course before creating resources, with the attributes defined in setUp.
        $previouscourses = $this->create_courses($this->previouscourseattributes, 1);

        // We have to insert those courses in the historic data tables.
        $previouscoursesids = $this->insert_previous_courses_in_historic_data($previouscourses);

        // We create the previous users...
        $previoususers = $this->create_and_enrol_students($previouscoursesids[0], 3, true);

        // We create the resources...
        $numberofresources = 3;
        $previousresources = array();
        $previousresources[$previouscourses[0]->id]['mod_page'] = $numberofresources;
        $resources = $this->create_resources($previousresources, $resourcesnames);

        // We create the log views for the previous users...
        $previouslogviews = array();
        $previouslogviews[$previoususers[0]->id] = array(10, 3, 5);
        $previouslogviews[$previoususers[1]->id] = array(4, 1, 2);
        $previouslogviews[$previoususers[2]->id] = array(0, 7, 7);

        foreach ($previouslogviews as $userid => $resourceslogviews) {
            foreach ($resourceslogviews as $resourceindex => $logviews) {
                $this->create_previous_course_logview($userid, $previouscoursesids[0], $resources[$resourceindex]->name,
                    'page', $logviews, $this->previousstartdate, $resourceindex);
            }
        }

        // We create the current users...
        $currentcourses = $this->create_courses($this->currentcourseattributes, 1);
        $currentcourseid = $currentcourses[0]->id;

        // We need to insert the courses associations in its table, because the recommendator looks at that table which
        // historic courses are related to the given current course.
        foreach ($previouscoursesids as $previouscoursesid) {
            $sql = "INSERT INTO {block_mycourse_course_assoc} (current_courseid, historic_courseid)
                    VALUES ($currentcourseid, $previouscoursesid)";
            $DB->execute($sql);
        }

        $currentusers = $this->create_and_enrol_students($currentcourses[0]->id, 3);

        // We set the created users as selected for receiving recommendations, but excluding the last one, to check that
        // effectively only the selected users are taken into account.
        foreach ($currentusers as $index => $currentuser) {
            if ($index + 1 === count($currentusers)) {
                break;
            }
            $sql = "INSERT INTO {block_mycourse_user_sel} (userid, courseid, year) VALUES(:v1, :v2, :v3)";
            $values = ['v1' => (int)$currentuser->id, 'v2' => $currentcourses[0]->id, 'v3' => 2016];
            $DB->execute($sql, $values);
        }

        // We create the resources...
        $numberofresources = 3;
        $currentresources = array();
        $currentresources[$currentcourses[0]->id]['mod_page'] = $numberofresources;
        $resources = array();
        $resources = $this->create_resources($currentresources, $resourcesnames);

        // We create the log views for the current users...
        $currentlogviews = array();
        $currentlogviews[$currentusers[0]->id] = array(3, 4, 6);
        $currentlogviews[$currentusers[1]->id] = array(7, 3, 2);
        // This user should not be taken into account for the recommendations since it's not in selected users' table.
        $currentlogviews[$currentusers[2]->id] = array(7, 3, 6);

        foreach ($currentlogviews as $userid => $resourceslogviews) {
            foreach ($resourceslogviews as $resourceindex => $logviews) {
                $this->create_logview($userid, $currentcourses[0]->id, $resources[$resourceindex]->id,
                    $eventname, $component, $this->currentstartdate, $logviews);
            }
        }

        // After the logs are created, we can call the function we're testing.
        $this->recommendator->create_associations($currentcourses[0]->id, 2,  new \null_progress_trace());

        // The number of rows of the table has to be equal to the number of selected students, otherwise, something is wrong.
        $actualrowcount = $DB->count_records('block_mycourse_assoc');
        $expectedrowcount = $DB->count_records('block_mycourse_user_sel');
        $this->assertEquals($expectedrowcount, $actualrowcount);

        // We query the actual values, and we store them in an index-based array beginning from 0, not using the ids as keys.
        $records = $DB->get_records('block_mycourse_assoc');
        $actuals = array_values($records);

        // We remove the "id" field of each record, because to make the assertion later is unnecessary.
        foreach ($actuals as $id => $actual) {
            unset($actual->id);
            $actuals[$id] = $actual;
        }

        // We create the expected values, determining the associations between users with the similarity coefficientes calculated
        // externaly.
        $expecteds = array();
        $expecteds[0] = new stdClass();
        $expecteds[0]->current_userid = $currentusers[0]->id;
        $expecteds[0]->current_courseid = $currentcourses[0]->id;
        $expecteds[0]->historic_userid = $previoususers[2]->id;
        $expecteds[0]->historic_courseid = $previouscoursesids[0];
        $expecteds[0]->week = '2';

        $expecteds[1] = new stdClass();
        $expecteds[1]->current_userid = $currentusers[1]->id;
        $expecteds[1]->current_courseid = $currentcourses[0]->id;
        $expecteds[1]->historic_userid = $previoususers[2]->id;
        $expecteds[1]->historic_courseid = $previouscoursesids[0];
        $expecteds[1]->week = '2';

        // Probably asserting each object, instead of the whole arrays of objects, will cause less trouble.
        foreach (array_keys($actuals) as $index) {
            $this->assertEquals($expecteds[$index], $actuals[$index]);
        }
    }

    public function test_create_recommendations() {
        global $DB;
        global $CFG;

        $this->resetAfterTest();
        $this->setAdminUser();

        $DB->execute('delete from {course} course where course.id <> 1');
        $coursesname = 'Operating Systems';

        $eventname = '\\mod_page\\event\\course_module_viewed';
        $component = 'mod_page';

        $resourcesnames = array('Page 1', 'Page 2', 'Page 3');
        $nextresourcesnames = array('Page 100', 'Page 101');

        // Previous course data...

        // We create the previous course...
        $previousyear = 2015;
        $previousstartdate = strtotime("06-01-$previousyear");
        $previousattributes = array('fullname' => $coursesname,
            'startdate' => $previousstartdate);

        $previouscourse = $this->create_courses($previousattributes, 1)[0];

        // We have to insert those courses in the historic data tables.
        $previouscoursesids = $this->insert_previous_courses_in_historic_data(array($previouscourse));

        // We create and enrol the previous users...
        $previoususers = $this->create_and_enrol_students($previouscoursesids[0], 3, true);

        // We create the previous resources...
        $previousresourcesnumber = count($resourcesnames);
        $previousresources = array();
        $previousresources[$previouscourse->id][$component] = $previousresourcesnumber;
        $previousresources = $this->create_resources($previousresources, $resourcesnames);

        // We create the log views of the previous course for previous resources and previous users...
        $previouslogviews = array();
        $previouslogviews[$previoususers[0]->id] = array(10, 3, 5);
        $previouslogviews[$previoususers[1]->id] = array(4, 1, 2);
        $previouslogviews[$previoususers[2]->id] = array(3, 5, 7);

        foreach ($previouslogviews as $previoususerid => $previousresourcesviews) {
            foreach ($previousresourcesviews as $resourceindex => $resourceviews) {
                $this->create_previous_course_logview($previoususerid, $previouscoursesids[0],
                    $previousresources[$resourceindex]->name, 'page', $resourceviews, $this->previousstartdate, $resourceindex);
            }
        }

        // We create the previous resources for the week + 1...

        $previousnexttimestamp = $previousstartdate + 7 * 24 * 60 * 60; // We add a week, in seconds...
        $previousnextresources = array();
        $previousnextresources[$previouscourse->id][$component] = count($nextresourcesnames);
        $previousnextresources = $this->create_resources($previousnextresources, $nextresourcesnames);

        // We create the log views of the previous course for previous next resources...
        $previousnextlogviews = array();
        $previousnextlogviews[$previoususers[0]->id] = array(1, 3);
        $previousnextlogviews[$previoususers[1]->id] = array(2, 2);
        $previousnextlogviews[$previoususers[2]->id] = array(6, 2);

        foreach ($previousnextlogviews as $previoususerid => $previousnextresourcesviews) {
            foreach ($previousnextresourcesviews as $resourceindex => $resourceviews) {
                $this->create_previous_course_logview($previoususerid, $previouscoursesids[0],
                    $previousnextresources[$resourceindex]->name, 'page', $resourceviews,
                    $this->previousstartdate, $resourceindex + count($previousresourcesviews)); // The index has been used before.
            }
        }

        // Current course data...

        // We create the current course...
        $currentyear = 2016;
        $currentstartdate = strtotime("06-01-$currentyear");
        $currentattributes = array('fullname' => $coursesname,
            'startdate' => $currentstartdate);
        $currentcourse = $this->create_courses($currentattributes, 1)[0];
        $currentcourseid = $currentcourse->id;

        // We need to insert the courses associations in its table, because the recommendator looks at that table which
        // historic courses are related to the given current course.
        foreach ($previouscoursesids as $previouscoursesid) {
            $sql = "INSERT INTO {block_mycourse_course_assoc} (current_courseid, historic_courseid)
                    VALUES ($currentcourseid, $previouscoursesid)";
            $DB->execute($sql);
        }

        // We create and enrol the current users...
        $currentusers = $this->create_and_enrol_students($currentcourse->id, 3);

        // We set the created users as selected for receiving recommendations, but excluding the last one, to check that
        // effectively only the selected users are taken into account.
        foreach ($currentusers as $index => $currentuser) {
            if ($index + 1 === count($currentusers)) {
                break;
            }
            $sql = "INSERT INTO {block_mycourse_user_sel} (userid, courseid, year) VALUES(:v1, :v2, :v3)";
            $values = ['v1' => (int)$currentuser->id, 'v2' => $currentcourse->id, 'v3' => $currentyear];
            $DB->execute($sql, $values);
        }

        // We create the previous resources...
        $currentresourcesnumber = count($resourcesnames);
        $currentresources = array();
        $currentresources[$currentcourse->id][$component] = $currentresourcesnumber;
        $currentresources = $this->create_resources($currentresources, $resourcesnames);

        // We create the log views of the current course for current resources and current users...
        $currentlogviews = array();
        $currentlogviews[$currentusers[0]->id] = array(3, 4, 6);
        $currentlogviews[$currentusers[1]->id] = array(7, 3, 2);
        // This user should not be taken into account for the recommendations since it's not in selected users' table.
        $currentlogviews[$currentusers[2]->id] = array(7, 3, 6);

        foreach ($currentlogviews as $currentuserid => $currentresourcesviews) {
            foreach ($currentresourcesviews as $resourceindex => $resourceviews) {
                $this->create_logview($currentuserid, $currentcourse->id, $currentresources[$resourceindex]->id, $eventname,
                    $component, $currentstartdate, $resourceviews);
            }
        }

        // We create the next resources for the week + 1...
        $currentnexttimestamp = $currentstartdate + 7 * 24 * 60 * 60; // We add a week, in seconds...
        $currentnextresources = array();
        $currentnextresources[$currentcourse->id][$component] = count($nextresourcesnames);
        $currentnextresources = $this->create_resources($currentnextresources, $nextresourcesnames);

        // Finally, we call the function.
        $this->recommendator->create_recommendations($currentcourse->id, 2,  new \null_progress_trace());

        // We query the actual values generated by the function...
        $actuals = $DB->get_records('block_mycourse_recs');

        // The number of users receiving recommendations has to be equal to the number of selected students, otherwise,
        // something is wrong.
        // So, we need to know for how many distinct users receive have been the recommendations generated.
        $sql = 'SELECT count(distinct(assoc.current_userid)) as c
                FROM   {block_mycourse_recs} recs
                INNER JOIN {block_mycourse_assoc} assoc
                ON recs.associationid = assoc.id';
        $actualrowcount = $DB->get_record_sql($sql)->c;

        $expectedrowcount = $DB->count_records('block_mycourse_user_sel');

        $this->assertEquals($expectedrowcount, $actualrowcount);

        // If there's no actual value, something is wrong.
        $this->assertFalse(empty($actuals));

        // We remove the attributes that we don't determine, i.e., the ones generated by database sequences.
        foreach ($actuals as $index => $actual) {
            unset($actual->id);
            unset($actual->associationid);
            $actuals[$index] = $actual;
        }
        // We want an 0-based index.
        $actuals = array_values($actuals);

        // We create the expected objects array with the values we are expecting...
        $expecteds = array();
        $expecteds[0] = new stdClass();
        $expecteds[0]->resourceid = $currentnextresources[0]->id;
        $expecteds[0]->priority = 0;
        $expecteds[0]->views = 0;

        $expecteds[1] = new stdClass();
        $expecteds[1]->resourceid = $currentnextresources[0]->id;
        $expecteds[1]->priority = 1;
        $expecteds[1]->views = 0;

        $expecteds[2] = new stdClass();
        $expecteds[2]->resourceid = $currentnextresources[1]->id;
        $expecteds[2]->priority = 0;
        $expecteds[2]->views = 0;

        $expecteds[3] = new stdClass();
        $expecteds[3]->resourceid = $currentnextresources[1]->id;
        $expecteds[3]->priority = 1;
        $expecteds[3]->views = 0;

        // We sort both arrays with the same criteria, to allow compare arrays' objects in a loop.
        usort($expecteds, array($this, 'sort_recommendations'));
        usort($actuals, array($this, 'sort_recommendations'));

        // Time to pray.
        foreach ($actuals as $index => $actual) {
            $this->assertEquals($expecteds[$index], $actual);
        }
    }

    protected function sort_recommendations($a, $b) {
        if ($a->resourceid < $b->resourceid) {
            return -1;
        } else if ($a->resourceid > $b->resourceid) {
            return 1;
        } else {
            if ($a->priority < $b->priority) {
                return -1;
            } else if ($a->priority > $b->priority) {
                return 1;
            } else {
                return 0;
            }
        }
    }

    /**
     * Gets class' method by name. Seems that for creating the ReflectionClass, it's necessary to
     * specify the full namespace.
     */
    protected static function get_method($name) {
        $class = new \ReflectionClass('\block_mycourse_recommendations\simple_recommendator');
        $method = $class->getMethod($name);
        $method->setAccessible(true);

        return $method;
    }

    public function test_keep_latest_logviews() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $inputs = array();
        $inputs[0] = new stdClass();
        $inputs[0]->userid = 1;
        $inputs[0]->courseid = 10;
        $inputs[0]->moduleid = 100;
        $inputs[0]->modulename = 'Module 100';
        $inputs[0]->logviews = 3;

        $inputs = array();
        $inputs[1] = new stdClass();
        $inputs[1]->userid = 2;
        $inputs[1]->courseid = 10;
        $inputs[1]->moduleid = 101;
        $inputs[1]->modulename = 'Module 101';
        $inputs[1]->logviews = 0;

        $inputs = array();
        $inputs[2] = new stdClass();
        $inputs[2]->userid = 2;
        $inputs[2]->courseid = 10;
        $inputs[2]->moduleid = 101;
        $inputs[2]->modulename = 'Module 101';
        $inputs[2]->logviews = 7;

        $inputs = array();
        $inputs[3] = new stdClass();
        $inputs[3]->userid = 2;
        $inputs[3]->courseid = 10;
        $inputs[3]->moduleid = 103;
        $inputs[3]->modulename = 'Module 102';
        $inputs[3]->logviews = 5;

        $testfunction = self::get_method('keep_latest_logviews');

        $functioninput = array();
        foreach ($inputs as $index => $input) {
            $functioninput[$index] = new query_result($input->userid, $input->courseid, $input->moduleid,
                $input->modulename, $input->logviews);
        }

        $actuals = $testfunction->invokeArgs($this->recommendator, array($functioninput, $functioninput))['previous'];

        $expecteds = array();
        foreach ($inputs as $index => $input) {
            $expecteds[$index] = new query_result($input->userid, $input->courseid, $input->moduleid,
                $input->modulename, $input->logviews);
        }
        // We remove the record that the function it's supposed to remove, and we re-align the array.
        unset($expecteds[1]);
        $expecteds = array_values($expecteds);

        foreach ($actuals as $index => $actual) {
            $this->assertEquals($expecteds[$index], $actual);
        }
    }

    public function test_save_logviews_by_resource() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        $testfunction = self::get_method('save_logviews_by_resource');

        $inputs = array();
        $inputs[0] = new stdClass();
        $inputs[0]->userid = 1;
        $inputs[0]->courseid = 10;
        $inputs[0]->moduleid = 100;
        $inputs[0]->modulename = 'Module 100';
        $inputs[0]->logviews = 3;

        $inputs[1] = new stdClass();
        $inputs[1]->userid = 2;
        $inputs[1]->courseid = 10;
        $inputs[1]->moduleid = 101;
        $inputs[1]->modulename = 'Module 101';
        $inputs[1]->logviews = 7;

        $inputs[2] = new stdClass();
        $inputs[2]->userid = 2;
        $inputs[2]->courseid = 10;
        $inputs[2]->moduleid = 103;
        $inputs[2]->modulename = 'Module 102';
        $inputs[2]->logviews = 5;

        $functioninput = array();
        $functioncurrentinput = array();

        foreach ($inputs as $index => $input) {
            $functioninput[$index] = new query_result($input->userid, $input->courseid, $input->moduleid,
                $input->modulename, $input->logviews);
            $functioncurrentinput[$index] = new query_result($input->userid, $input->courseid, $input->moduleid,
                $input->modulename, $input->logviews);
        }

        $actuals = $testfunction->invokeArgs($this->recommendator, array($functioninput, $functioncurrentinput));

        $expecteds = array();
        foreach ($inputs as $index => $input) {
            $expecteds[$input->moduleid] = $input->logviews;
        }

        foreach ($actuals as $index => $actual) {
            $this->assertEquals($expecteds[$index], $actual);
        }
    }

}

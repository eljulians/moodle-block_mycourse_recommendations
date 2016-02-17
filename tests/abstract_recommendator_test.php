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
 * Unit tests for abstract recommendations of mycourse_recommendations block.
 *
 * @package    block_mycourse_recommendations
 * @category   phpunit
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/recommendator/abstract_recommendator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/associator/cosine_similarity_associator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/decimal_matrix.php');

use block_mycourse_recommendations\abstract_recommendator;
use block_mycourse_recommendations\cosine_similarity_associator;
use block_mycourse_recommendations\decimal_matrix;

/**
 * Unfortunatelly, seems that this dirty workaround is the only way to test abstract class' implemented functions.
 */
class concrete_recommendator extends abstract_recommendator {

    public function __construct($associator) {
        parent::__construct($associator);
        null; // The codechecker will throw a warning if we don't do something more apart from calling parent's constructor...
    }

    /**
     * Parents abstract method must be implemented, so we do this dirty (another) workaround.
     */
    public function create_recommendations($courseid, $currentweek) {
        null;
    }

    /**
     * Parents abstract method must be implemented, so we do this dirty (another) workaround.
     */
    public function create_associations($courseid, $currentweek) {
        null;
    }
}

class block_mycourse_recommendations_abstract_recommendator_testcase extends advanced_testcase {

    protected $recommendator;
    protected $courses;
    protected $currentyear;
    protected $courseattributes;

    protected function setUp() {
        parent::setUp();
        $this->recommendator = new concrete_recommendator(new cosine_similarity_associator(new decimal_matrix));
        $this->currentyear = 2016;
        $this->courseattributes = array('fullname' => 'Software Engineering II',
                                        'startdate' => strtotime("16-02-$this->currentyear"));
        $this->courses = $this->create_courses($this->courseattributes, 1);
    }

    protected function tearDown() {
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

    /**
     * Tests that the function selects the half of the students of a course, querying the table after calling the function.
     */
    public function test_select_students() {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Let's say that the course has 20 students. Only the half is selected, so, the expected value will be the half.
        $coursestudents = 20;
        $expected = $coursestudents / 2;

        // We create and enrol users, using the created course in setup.
        $this->create_and_enrol_students($this->courses[0]->id, $coursestudents);

        // We call the function that will insert the users into the database.
        $this->recommendator->select_students($this->courses[0]->id, $this->currentyear);

        // We create the query that counts the number of rows in the selected users table, after doing the insertion.
        $sql = 'SELECT count(*) AS count
                FROM   {block_mycourse_user_sel}';
        $actual = $DB->get_record_sql($sql)->count;

        $this->assertEquals($expected, $actual);
    }
}

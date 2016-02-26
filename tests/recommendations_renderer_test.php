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

require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/renderer/recommendations_renderer.php');

use block_mycourse_recommendations\recommendations_renderer;

class block_mycourse_recommendations_recommendations_renderer_testcase extends advanced_testcase {

    protected function setUp() {
        parent::setUp();
        null;
    }

    protected function tearDown() {
        parent::tearDown();
        null;
    }

    protected function create_resources($courseid, $number) {
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_page');
        $resources = array();

        for ($index = 0; $index < $number; $index++) {
            $newresource = $generator->create_instance(array('course' => $courseid));
            array_push($resources, $newresource);
        }

        return $resources;
    }

    public function test_render_recommendations_empty() {
        $this->resetAfterTest();
        $this->setAdminUser();

        $recommendations = array();

        $expected = get_string('norecommendations', 'block_mycourse_recommendations');

        $actual = recommendations_renderer::render_recommendations($recommendations);

        $this->assertEquals($expected, $actual);
    }

    public function test_render_recommendations_below() {
        global $COURSE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $resourcenumber = recommendations_renderer::MAX_RECOMMENDATIONS - 1;

        $course = $this->getDataGenerator()->create_course();
        $courseid = $course->id;
        $COURSE = $course;

        $resources = $this->create_resources($courseid, $resourcenumber);

        $recommendations = array();

        foreach ($resources as $index => $resource) {
            $recommendations[$index] = new stdClass();
            $recommendations[$index]->resourceid = $resource->cmid;
            $recommendations[$index]->priority = $index;
        }

        $actual = recommendations_renderer::render_recommendations($recommendations);

        $expected = '<ol>';

        foreach ($resources as $resource) {
            $expected .= '<li>';
            $expected .= $resource->name;
            $expected .= '</li>';
        }

        $expected .= '</ol>';

        $this->assertEquals($expected, $actual);
    }

    public function test_render_recommendations_equal() {
        global $COURSE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $resourcenumber = recommendations_renderer::MAX_RECOMMENDATIONS;

        $course = $this->getDataGenerator()->create_course();
        $courseid = $course->id;
        $COURSE = $course;

        $resources = $this->create_resources($courseid, $resourcenumber);

        $recommendations = array();

        foreach ($resources as $index => $resource) {
            $recommendations[$index] = new stdClass();
            $recommendations[$index]->resourceid = $resource->cmid;
            $recommendations[$index]->priority = $index;
        }

        $actual = recommendations_renderer::render_recommendations($recommendations);

        $expected = '<ol>';

        foreach ($resources as $resource) {
            $expected .= '<li>';
            $expected .= $resource->name;
            $expected .= '</li>';
        }

        $expected .= '</ol>';

        $this->assertEquals($expected, $actual);
    }

    public function test_render_recommendations_above() {
        global $COURSE;

        $this->resetAfterTest();
        $this->setAdminUser();

        $resourcenumber = recommendations_renderer::MAX_RECOMMENDATIONS + 1;

        $course = $this->getDataGenerator()->create_course();
        $courseid = $course->id;
        $COURSE = $course;

        $resources = $this->create_resources($courseid, $resourcenumber);

        $recommendations = array();

        foreach ($resources as $index => $resource) {
            $recommendations[$index] = new stdClass();
            $recommendations[$index]->resourceid = $resource->cmid;
            $recommendations[$index]->priority = $index;
        }

        $actual = recommendations_renderer::render_recommendations($recommendations);

        $expected = '<ol>';

        $resources = array_values($resources);

        foreach ($resources as $index => $resource) {
            if ($index === 3) {
                break;
            }

            $expected .= '<li>';
            $expected .= $resource->name;
            $expected .= '</li>';
        }

        $expected .= '</ol>';

        $this->assertEquals($expected, $actual);
    }

}


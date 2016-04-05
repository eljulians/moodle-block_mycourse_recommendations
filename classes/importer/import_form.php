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
 * Form to import historic data from CSV files.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot . '/user/editlib.php');
require_once($CFG->dirroot . '/lib/csvlib.class.php');

define('MAX_FILES', 3);
define('MAX_BYTES', 1000000);

/**
 * Class import_form to import historic data from CSV files.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class import_form extends \moodleform {

    /**
     * Defines the form, which will have:
     *  - Input for courses csv.
     *  - Input for users csv.
     *  - Input for logs csv.
     *  - Select for csv delimiter.
     *  - Select for files encoding.
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'settingsheader', get_string('upload', 'block_mycourse_recommendations'));
        $mform->addElement('static', 'description', '', get_string('upload_desc', 'block_mycourse_recommendations'));

        $mform->addElement('static', 'description', get_string('coursefile', 'block_mycourse_recommendations'),
                            get_string('coursefile_desc', 'block_mycourse_recommendations'));
        $mform->addElement('filepicker', 'courses', '');
        $mform->addRule('courses', null, 'required');

        $mform->addElement('static', 'description', get_string('usersfile', 'block_mycourse_recommendations'),
                            get_string('usersfile_desc', 'block_mycourse_recommendations'));
        $mform->addElement('filepicker', 'users', '');
        $mform->addRule('users', null, 'required');

        $mform->addElement('static', 'description', get_string('logsfile', 'block_mycourse_recommendations'),
                            get_string('logsfile_desc', 'block_mycourse_recommendations'));
        $mform->addElement('filepicker', 'logs', '');
        $mform->addRule('logs', null, 'required');

        $choices = \csv_import_reader::get_delimiter_list();
        $mform->addElement('select', 'delimiter_name', get_string('csvdelimiter', 'tool_uploaduser'), $choices);
        if (array_key_exists('cfg', $choices)) {
            $mform->setDefault('delimiter_name', 'cfg');
        } else if (get_string('listsep', 'langconfig') == ';') {
            $mform->setDefault('delimiter_name', 'semicolon');
        } else {
            $mform->setDefault('delimiter_name', 'comma');
        }

        $choices = \core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'tool_uploaduser'), $choices);
        $mform->setDefault('encoding', 'UTF-8');

        $this->add_action_buttons(false, get_string('uploaddata', 'block_mycourse_recommendations'));
    }

}

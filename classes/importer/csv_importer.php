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
 * Importation of data from CSV.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/lib/csvlib.class.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/db/database_helper.php');

use block_mycourse_recommendations\database_helper;

/**
 * The class that imports data from a CSV file to the historic tables.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class csv_importer {

    /**
     * Receiving the three required CSVs, performs the importation of the data of a course: course info, enrolled users,
     * and log info. The data is imported to a total of three different tables. All operations are done in a unique
     * transaction: if something fails (a CSV does not respect the format, an importing course exists, etc.), nothing
     * will be saved.
     *
     * @param object $formdata The data submited in form.
     * @param file $coursefile The CSV file with the information about the course.
     * @param file $usersfile The CSV file with the information about the users enrolled in courses.
     * @param file $logsfile The CSV file with the information about the log views of the users.
     */
    public static function import_data($formdata, $coursefile, $usersfile, $logsfile) {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        try {
            $courseid = self::import_course($coursefile, $formdata);

            $transaction->allow_commit();
        } catch (\Exception $e) {
            $transaction->rollback($e);
            echo $e->getMessage();
        }
        
    }

    public static function import_course($coursefile, $formdata) {
        global $DB;

        $iid = \csv_import_reader::get_new_iid('coursefile');
        $csvreader = new \csv_import_reader($iid, 'coursefile');

        $csvreader->load_csv_content($coursefile, $formdata->encoding, $formdata->delimiter_name);

        $csvreader->init();

        $fields = $csvreader->get_columns();
        $delimiter = $csvreader->get_delimiter($formdata->delimiter_name);

        while ($fields) {
            $record = new \stdClass();
            $record->fullname = $fields[0];
            $record->shortname = $fields[1];
            $record->startdate = $fields[2];
            $record->idnumber = $fields[3];
            $record->category = $fields[4];

            $courseid = $DB->insert_record('block_mycourse_hist_course', $record);

            $fields = $csvreader->next();
        }

        $csvreader->close();

        return $courseid;
    }

    /**
     * @TODO save information in table.
     */
    public static function import_users($usersfile, $formdata, $course) {
        $iid = \csv_import_reader::get_new_iid('usersfile');
        $csvreader = new \csv_import_reader($iid, 'usersfile');

        $csvreader->load_csv_content($usersfile, $formdata->encoding, $formdata->delimiter_name);

        $csvreader->init();

        $fields = $csvreader->get_columns();

        while ($fields) {
            var_dump($fields);
            $fields = $csvreader->next();
        }

        $csvreader->close();
    }

    /**
     * @TODO save information in table.
     */
    public static function import_logs($logsfile, $formdata, $course) {
        $iid = \csv_import_reader::get_new_iid('logsfile');
        $csvreader = new \csv_import_reader($iid, 'logsfile');

        $csvreader->load_csv_content($logsfile, $formdata->encoding, $formdata->delimiter_name);

        $csvreader->init();

        $fields = $csvreader->get_columns();

        while ($fields) {
            var_dump($fields);
            $fields = $csvreader->next();
        }

        $csvreader->close();
    }
}

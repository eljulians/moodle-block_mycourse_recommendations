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

    public static function import_data($formdata, $coursefile, $usersfile, $logsfile) {
        $iid = \csv_import_reader::get_new_iid('asdf');
        $cir = new \csv_import_reader($iid, 'asdf');

        self::import_course($coursefile, $formdata);
        self::import_users($usersfile, $formdata, 0);
        self::import_logs($logsfile, $formdata, 0);
/*
        $readcount = $cir->load_csv_content($filecontent, $formdata->encoding, $formdata->delimiter_name);
        
        $cir->init();
        
        $fields = $cir->get_columns(); 

        while ($fields) {
            var_dump($fields);
            $fields = $cir->next();
        }
        
        $cir->close();
        
        //var_dump($formdata);
        */
    }

    public static function import_course($coursefile, $formdata) {
        $iid = \csv_import_reader::get_new_iid('coursefile');
        $csvreader = new \csv_import_reader($iid, 'coursefile');

        $csvreader->load_csv_content($coursefile, $formdata->encoding, $formdata->delimiter_name);

        $csvreader->init();

        $fields = $csvreader->get_columns();
        echo "course";
        while ($fields) {
            var_dump($fields);
            $fields = $csvreader->next();
        }


        $csvreader->close();
    }

    public static function import_users($usersfile, $formdata, $course) {
        $iid = \csv_import_reader::get_new_iid('usersfile');
        $csvreader = new \csv_import_reader($iid, 'usersfile');

        $csvreader->load_csv_content($usersfile, $formdata->encoding, $formdata->delimiter_name);

        $csvreader->init();

        $fields = $csvreader->get_columns();
        echo "users";
        while ($fields) {
            var_dump($fields);
            $fields = $csvreader->next();
        }

        $csvreader->close();
    }

    public static function import_logs($logsfile, $formdata, $course) {
        $iid = \csv_import_reader::get_new_iid('logsfile');
        $csvreader = new \csv_import_reader($iid, 'logsfile');

        $csvreader->load_csv_content($logsfile, $formdata->encoding, $formdata->delimiter_name);

        $csvreader->init();

        $fields = $csvreader->get_columns();
        echo "logs";
        while ($fields) {
            var_dump($fields);
            $fields = $csvreader->next();
        }

        $csvreader->close();
    }
}

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
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

require_once('abstract_associator.php');
require_once($CFG->dirroot . '/blocks/mycourse_recommendations/classes/matrix/abstract_matrix.php'); // Include the code to test.

use block_mycourse_recommendations\abstract_associator;

class cosine_similarity_associator implements abstract_associator {

    private $matrix;

    public function __construct($matrixinstance) {
        $this->matrix = $matrixinstance;
    }

    public function find_associations($currentdata, $historicdata) {

    }

    private function cosine_similarity($vector1, $vector2) {

        return null;
    }

    private function dot_product($vector1, $vector2) {

        return null;
    }

    private function vector_module($vector) {

        return null;
    }
}

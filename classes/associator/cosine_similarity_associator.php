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

    public function create_associations_matrix($currentdata, $historicdata) {
        return null;
    }

    private function cosine_similarity($vector1, $vector2) {
        $numerator = $this->dot_product($vector1, $vector2);
        $denominator = $this->vector_module($vector1) * $this->vector_module($vector2);

        $result = $numerator / $denominator;

        return $result;
    }

    private function dot_product($vector1, $vector2) {
        $length = min(count($vector1), count($vector2));
        $result = 0;

        for ($index = 0; $index < $length; $index++) {
            $result += $vector1[$index] * $vector2[$index];
        }

        return $result;
    }

    private function vector_module($vector) {
        $result = 0;

        for ($index = 0; $index < count($vector); $index++) {
            $result += pow($vector[$index], 2);
        }

        $result = sqrt($result);

        return $result;
    }
}

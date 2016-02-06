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
 * class block_mycourse_recommendations.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2015 onwards Iñaki Arenaza & Mondragon Unibertsitatea
 *             2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use block_mycourse_recommendations\abstract_recommendator;

class block_mycourse_recommendations extends block_base {

    /**
     * Initialises the block
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_mycourse_recommendations');
    }

}

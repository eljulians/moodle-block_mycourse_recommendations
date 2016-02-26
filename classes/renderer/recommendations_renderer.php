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

defined('MOODLE_INTERNAL') || die();

class recommendations_renderer {

    const MAX_RECOMMENDATIONS = 3;
    const NO_RECOMMENDATIONS = 'The are not recommendations for you this week.';

    /**
     * Generates the HTML for the given recommendations. The maximum number of recommendations is defined by
     * self::MAX_RECOMMENDATIONS constant.
     *
     * @param array $recommendations Recommendations of the week and course.
     * @return string The formatted HTML for the recommendations.
     */
    public static function render_recommendations($recommendations) {
        global $COURSE;

        if (empty($recommendations)) {
            return self::NO_RECOMMENDATIONS;
        }

        $modinfo = get_fast_modinfo($COURSE->id);
        $recommendations = array_values($recommendations);
        $output = '<ol>';

        for ($index = 0; $index < self::MAX_RECOMMENDATIONS; $index++) {
            $recommendation = $recommendations[$index];
            $cminfo = $modinfo->get_cm($recommendation->resourceid);

            $output .= '<li>';
            $output .= $cminfo->get_formatted_name();
            $output .= '</li>';
        }

        $output .= '</ol>';

        return $output;
    }
}

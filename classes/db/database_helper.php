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

use \stdClass;

defined('MOODLE_INTERNAL') || die();

class database_helper {

    public $sql;

    public function __construct() {
        $this->sql = "
       select
       logs.id,
       logs.format,
       logs.section,
       logs.resource_type,
       logs.moduleid,
       logs.module_name,
       logs.userid,
       logs.log_views,
       logs.grades
  from (select modules.course,
               c.format,
               cm.section,
               cm.id,
               modules.resource_type,
               modules.id as moduleid,
               modules.name as module_name,
               ra.userid,
               weeks.course_week,
               (select count(l.time)
                  from {log} l
                 where l.userid = ra.userid
                   and l.course = modules.course
                   and l.module = modules.resource_type
                   and date_trunc('week', to_timestamp(l.time)) =weeks.course_week
                   and (  (   module <> 'assign'
                          and info = cast(modules.id as varchar))
                       or (   module = 'assign'
                          and cmid = cm.id)))
               +
               (select count(lsl.timecreated)
                  from {logstore_standard_log} lsl
                 where lsl.userid = ra.userid
                   and lsl.courseid = modules.course
                   and date_trunc('week', to_timestamp(lsl.timecreated)) =
                       weeks.course_week
                   and (  (   component like '%assign%'
                          and modules.resource_type = 'assign')
                       or (   component like 'mod%'
                          and modules.resource_type = substr(component, 5))
                       or (modules.resource_type = component))
                   and cm.id = lsl.contextinstanceid
                   and target not in ('user', 'course')
                   and component not in ('core', 'report_log'))
               as log_views,
               (SELECT case
                         when gi.gradetype = 1 then
                          cast(gg.finalgrade as varchar)
                         when (gi.gradetype = 2 and gi.scaleid = 3 and
                              gg.finalgrade = 1) then
                          'Si'
                         when (gi.gradetype = 2 and gi.scaleid = 3 and
                              gg.finalgrade = 2) then
                          'No'
                       end as grade
                  FROM {grade_grades} gg, {grade_items} gi
                  left outer join {scale} s
                    on (gi.scaleid = s.id)
                 WHERE (gi.id = gg.itemid)
                   and gi.courseid = weeks.course
                   and gi.iteminstance = modules.id
                   and gg.userid = ra.userid
                   and gi.itemmodule = modules.resource_type
                   and date_trunc('week', to_timestamp(gg.timemodified)) = weeks.course_week
                   AND gi.itemmodule is not null
                   AND gi.courseid = modules.course
                ) as grades
          from {role_assignments} ra,
               {role} r,
               {context} ctx,
               {course} c,
               {course_modules} cm,
               (select m.name as resource_type,
                       m.id   as resourcetypeid,
                       course,
                       p.id,
                       p.name
                  from {page} p, {modules} m
                 where m.name = 'page'
                union
                select m.name, m.id as resourcetypeid, course, r.id, r.name
                  from {resource} r, {modules} m
                 where m.name = 'resource'
                union
                select m.name, m.id as resourcetypeid, course, u.id, u.name
                  from {url} u, {modules} m
                 where m.name = 'url'
                union
                select m.name, m.id as resourcetypeid, course, f.id, f.name
                  from {folder} f, {modules} m
                 where m.name = 'folder'
                union
                select m.name, m.id as resourcetypeid, course, b.id, b.name
                  from {book} b, {modules} m
                 where m.name = 'book'
                union
                select m.name, m.id as resourcetypeid, course, f.id, f.name
                  from {forum} f, {modules} m
                 where m.name = 'forum'
                union
                select m.name, m.id as resourcetypeid, course, a.id, a.name
                  from {assign} a, {modules} m
                 where m.name = 'assign') modules,
               (select course,
                       date_trunc('week', to_timestamp(l.time)) as course_week
                  from {log} l
                 where module not in ('user', 'course')
                union
                select cast(courseid as integer) as course,
                       date_trunc('week', to_timestamp(lsl.timecreated)) as course_week
                  from {logstore_standard_log} lsl
                 where target not in ('user', 'course')
                   and component not in ('core', 'report_log')
                union
                SELECT gi.courseid,
                       date_trunc('week', to_timestamp(gg.timemodified))
                  FROM {grade_grades} gg, {grade_items} gi
                 WHERE (gi.id = gg.itemid)
                   and gg.timemodified is not null) weeks
         where (ra.roleid = r.id)
           and (ra.contextid = ctx.id)
           and (c.id = ctx.instanceid)
           and (modules.course = cm.course)
           and (modules.resourcetypeid = cm.module)
           and (modules.id = cm.instance)
           and (c.id = modules.course)
           and modules.course = weeks.course
           and ctx.contextlevel = 50
           and r.shortname = 'student' ) logs
 where logs.course = %courseid
   and ((extract(YEAR from logs.course_week) - %year) * 52) + extract(WEEK from logs.course_week)
       between %coursestartweek and %currentweek
 order by logs.log_views desc;
        ";
    }

    /**
     * Queries the needed data for making associations. It queries the number of accesses a
     * user makes to a module. This function is used both for historic and current data
     * (determined by the parameters).
     * The needed data is the following:
     * moduleid: the id of the module (resource) that has been accessed.
     * module_name: the name of the module (resource) that has been accessed.
     * userid: the id of the user who has accessed the module.
     * log_views: the number of times the user has accessed the module.
     *
     * @param int $courseid The id of the course from which the data will be extracted.
     * @param int $year The year the course is/was teached.
     * @param int $coursestartweek The start week of the course.
     * @param int $currentweek The week until the data will be queried. For a week n,
     * the queried data will be from the first week, to the week (n-1). When querying
     * historic data, this week will (presumably) be the end week of the course.
     *
     * @return array The recordset returned by database. A not-associative array, it doesn't
     * seem to be an obvious way to identificate each module access count in an unique way.
     */
    public function query_data($courseid, $year, $coursestartweek, $currentweek) {
        global $DB;

        $sql = $this->sql;

        $sql = str_replace('%courseid', $courseid, $sql);
        $sql = str_replace('%year', $year, $sql);
        $sql = str_replace('%coursestartweek', $coursestartweek, $sql);
        $sql = str_replace('%currentweek', $currentweek, $sql);

        $records = $DB->get_recordset_sql($sql);

        return $records;
    }

    /**
     * Inserts the calculated associations between students for a specific course, into
     * the associations table. Instead of receiving an associative array with all the fields,
     * an array of each field is received, for the encapsulation of the data logic in the class
     * (in this case, the names of the columns). A little more work at the creation of the
     * function, but for a proper encapsulation that results in an easier maintenance.
     * Obviously, arrays' values' indexes will be aligned.
     *
     * @param int $number The number of associations to insert.
     * @param array $currentusersids The ids of the current users, which are associated to
     * historic users.
     * @param int $currentcourseid The id of the course the current users belong to. As the
     * associations are calculed for a course, it's a single int value.
     * @param array $historicuserid The ids of the historics users, which are associated to current
     * users.
     * @param int $historiccourseid The id of the course the historics users belong to. As the
     * associations are calculed for a course, it's a single int value.
     * @param int $week The week for which the associations have been calculed. As the associations are
     * calculated for a week, it's a single int value.
     */
    public function insert_associations($number, $currentusersids, $currentcourseid, $historicuserids, $historiccourseid, $week) {
        global $DB;

        $associations = array();

        for ($index = 0; $index < $number; $index++) {
            $association = new stdClass();

            $association->current_userid = $currentusersids[$index];
            $association->current_courseid = $currentcourseid;
            $association->historic_userid = $historicuserids[$index];
            $association->historic_courseid = $historiccourseid;
            $association->week = $week;

            array_push($associations, $association);
        }

        $DB->insert_records('block_mycourse_assoc', $associations);
    }

    /**
     * Inserts the calculated recommendations for the current students, into the recommendations
     * table. Instead of receiving an associative array with all the fields, an array of each field
     * is received, for the encapsulation of the data logic in the class (in this case, the names of
     * the columns). A little more work at the creation of the function, but for a proper encapsulation
     * that results in an easier maintenance.
     * Obviously, arrays' values' indexes will be aligned.
     *
     * @param int $number The number of recommendations to insert.
     * @param array associationsids The id of the calculed associations.
     * @param array resourcesids The ids of the recommended resources.
     * @param orders The orders in which will be displayed the resource, in ascendent order.
     */
    public function insert_recommendations($number, $associationids, $resourcesids, $priorities) {
        global $DB;

        $recommendations = array();

        for ($index = 0; $index < $number; $index++) {
            $recommendation = new stdClass();

            $recommendation->associationid = $associationids[$index];
            $recommendation->resourceid = $resourcesids[$index];
            $recommendation->priority = $priorities[$index];

            array_push($recommendations, $recommendation);
        }

        $DB->insert_records('block_mycourse_recs', $recommendations);
    }

}

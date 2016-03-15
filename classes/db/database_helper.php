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
 * Database encapsulation.
 *
 * @package    block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_mycourse_recommendations;

require_once('query_result.php');

use \stdClass;
use block_mycourse_recommendations\query_result;

defined('MOODLE_INTERNAL') || die();

/**
 * Class database_helper for encapsulating all the database operations, so, the other classes don't have to
 * know database's tables' names, columns, etc.
 *
 * @package block_mycourse_recommendations
 * @copyright  2016 onwards Julen Pardo & Mondragon Unibertsitatea
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class database_helper {

    /**
     * The query to get the logviews by weeks, user and course.
     * @var string
     */
    public $sql;

    /**
     * database_helper constructor.
     */
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
       logs.course_week as view_date,
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
    and logs.log_views = 0
 order by logs.userid;
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
     * @param int $userid The user id to calculate the views of. Default is null, so, by default the
     * logviews are queried for all the users of the course.
     * @param boolean $ignoreweeks If the weeks have to be ignored. By default is false, so, by default
     * the weeks are taken into consideration
     * @param boolean $onlyunviewed If only the unviewed resources have to be queried. By default is false,
     * so, by default all the resources are queried, viewed or not.
     *
     * @return array An object for each record in recordset.
     */
    public function query_data($courseid, $year, $coursestartweek, $currentweek, $userid = null,
                               $ignoreweeks = false, $onlyunviewed = false) {
        global $DB;

        $sql = $this->sql;

        if (!is_null($userid)) {
            $sql = str_replace('where logs.course = %courseid', "where logs.userid = $userid and logs.course = %courseid ", $sql);
        }

        $sql = str_replace('%courseid', $courseid, $sql);
        if (!$ignoreweeks) {
            $sql = str_replace('%year', $year, $sql);
            $sql = str_replace('%coursestartweek', $coursestartweek, $sql);
            $sql = str_replace('%currentweek', $currentweek, $sql);
        } else {
            $sql = str_replace('and ((extract(YEAR from logs.course_week) - %year) * 52) + extract(WEEK from logs.course_week)',
                '', $sql);
            $sql = str_replace('between %coursestartweek and %currentweek', '', $sql);
        }

        if (!$onlyunviewed) {
            $sql = str_replace('and logs.log_views = 0', '', $sql);
        }

        $recordset = $DB->get_recordset_sql($sql);

        $queryresults = array();
        $index = 0;

        foreach ($recordset as $record) {
            $userid = $record->userid;
            $moduleid = $record->moduleid;
            $modulename = $record->module_name;
            $logviews = $record->log_views;
            $grades = $record->grades;
            $resourcetype = $record->resource_type;

            // The date is comming in yyyy-mm-dd hh:mm:ss+01, so, we add that hour manually, because strtotime doesn't do it.
            $timestamp = strtotime($record->view_date) + 3600;

            $queryresults[$index] = new query_result($userid, $courseid, $moduleid, $modulename, $logviews,
                                                     $grades, $resourcetype, $timestamp);
            $index++;
        }

        $recordset->close();

        return $queryresults;
    }

    /**
     * Queries the logviews of a course, created between the given weeks, for the specified user (if any).
     *
     * @param int $courseid The id of the historic course.
     * @param int $year The year the course was teached at.
     * @param int $coursestartweek The start week of the course.
     * @param int $currentweek The week the associations are being calculated at.
     * @param int $userid The user id to query data of, by default null, so it will be queried for every user.
     * @param bool $gradepass If the grade has to be taken into account (>= 5), by default false.
     * @return array Each row of the logs.
     */
    public function query_historic_course_data($courseid, $year, $coursestartweek, $currentweek,
                                               $userid = null, $gradepass = false) {
        global $DB;

        $sql = "SELECT logs.id,
                       logs.userid,
                       logs.resourcename,
                       logs.resourcetype,
                       logs.resourceid,
                       logs.views
                FROM   {block_mycourse_hist_data} logs
                INNER JOIN {block_mycourse_hist_course} course
                    ON logs.courseid = course.id
                INNER JOIN {block_mycourse_hist_enrol} enrol
                    ON enrol.courseid = course.id
                    AND enrol.userid = logs.userid
                WHERE ((EXTRACT('year' FROM date_trunc('year', to_timestamp(course.startdate))) - %year) * 52)
	                + EXTRACT('week' FROM date_trunc('week', to_timestamp(course.startdate)))
                  BETWEEN %coursestartweek AND %currentweek
                    AND logs.userid = %userid
                    AND course.id = %courseid
                    AND enrol.grade >= 5";

        if (is_null($userid)) {
            $sql = str_replace('AND logs.userid = %userid', '', $sql);
        }

        if (!$gradepass) {
            $sql = str_replace('AND enrol.grade >= 5', '', $sql);
        }

        $sql = str_replace('%courseid', $courseid, $sql);
        $sql = str_replace('%year', $year, $sql);
        $sql = str_replace('%coursestartweek', $coursestartweek, $sql);
        $sql = str_replace('%currentweek', $currentweek, $sql);
        $sql = str_replace('%userid', $userid, $sql);

        $records = $DB->get_records_sql($sql);

        $queryresults = array();

        foreach ($records as $record) {
            $userid = $record->userid;
            $resourcename = $record->resourcename;
            $logviews = $record->views;
            $moduleid = $record->resourceid;

            array_push($queryresults, new query_result($userid, $courseid, $moduleid, $resourcename, $logviews));
        }

        return $queryresults;
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
     * @param array $historicuserids The ids of the historics users, which are associated to current
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

    /**
     * Queries the student users of the given course. The student roleid is 5, and the context level
     * of the courses 50.
     *
     * @param int $courseid The course to query students from.
     * @return array Index-based array of students' ids.
     */
    public function get_students_from_course($courseid) {
        global $DB;

        $sql = 'SELECT users.id  AS userid,
                       course.id AS courseid
                FROM   {user} users
                INNER JOIN {role_assignments} ra
                    ON users.id = ra.userid
                INNER JOIN {context} context
                    ON ra.contextid = context.id
                INNER JOIN {course} course
                    ON context.instanceid = course.id
                WHERE  context.contextlevel = 50
                    AND ra.roleid = 5
                    AND course.id = ?';

        $users = array();

        $recordset = $DB->get_recordset_sql($sql, array($courseid));

        foreach ($recordset as $record) {
            array_push($users, $record->userid);
        }

        $recordset->close();

        return $users;
    }

    /**
     * Inserts the randomly selected user for the given course and year in the corresponding table.
     * The DML instruction is constructed manually because the table doesn't have an 'id' column,
     * and the '$BD->insert_record' throws an exception if it doesn't find a column with that name.
     * If the course is not going to receive recommendations because it is not personalizable, we
     * save it also, because we need to know that we don't have to calculate recommendations for the
     * course, even if it has a block instance. If it is not personalizable, we don't select users.
     *
     * @param array $selections The ids of the users that will receive the recommendations.
     * @param int $courseid The course where the users will receive the recommendations.
     * @param int $year Course's year.
     */
    public function insert_selections($selections, $courseid, $year) {
        global $DB;

        foreach ($selections as $selection) {
            $sql = "INSERT INTO {block_mycourse_user_sel} (userid, courseid, year) VALUES(:v1, :v2, :v3)";
            $values = ['v1' => (int)$selection, 'v2' => $courseid, 'v3' => $year];

            $DB->execute($sql, $values);
        }
    }

    /**
     * Inserts the given course into the table where the selected courses are kept. If the course is not going to
     * be personalizable, the "active" field will be set to false, since it won't receive recommendations.
     * Both "personalizable" and "active" fields are defined as integer in the database, so it's needed to "parse" the
     * boolean values to integers.
     *
     * @param int $courseid The course id.
     * @param int $year Course's teaching year.
     * @param boolean $personalizable If the course is personalizable or not.
     */
    public function insert_course_selection($courseid, $year, $personalizable) {
        global $DB;

        $active = ($personalizable) ? 1 : 0;
        $personalizable = ($personalizable) ? 1 : 0;

        $sql = "INSERT INTO {block_mycourse_course_sel} (courseid, year, active, personalizable) VALUES(:v1, :v2, :v3, :v4)";
        $values = ['v1' => (int)$courseid, 'v2' => $year, 'v3' => $active, 'v4' => $personalizable];
        $DB->execute($sql, $values);
    }

    /**
     * Queries the active courses to receive the recommendations.
     *
     * @return array Active courses.
     */
    public function get_selected_active_courses() {
        global $DB;

        $records = $DB->get_records('block_mycourse_course_sel', array('active' => '1'));

        return $records;
    }

    /**
     * Sets the given course to inactive, so, it won't receive recommendations anymore.
     *
     * @param int $courseid The course to set inactive.
     */
    public function set_course_inactive($courseid) {
        global $DB;

        $sql = "UPDATE {block_mycourse_course_sel}
                SET    active = 0
                WHERE  courseid = $courseid";

        $DB->execute($sql);
    }

    /**
     * Sets the courses for the given year to inactive, so, they won't receive recommendations anymore.
     *
     * @param int $year The year of the courses to set inactive.
     */
    public function set_courses_of_year_inactive($year) {
        global $DB;

        $sql = "UPDATE {block_mycourse_course_sel}
                SET    active = 0
                WHERE  year = $year";

        $DB->execute($sql);
    }

    /**
     * This function finds, for the given current coures id, the same course but in previous teachings, in Moodle's core tables.
     * For that, the function looks up into the {block_mycourse_hist_course} table, finding courses with the same full name.
     * So, if it is wanted to generate recommendations for the given current course, an historic course must exist
     * in the mentioned table, so, the data importation has to be done before.
     *
     * @param int $currentcourseid The id of the current course.
     * @param int $currentyear The year the current course is being teached in.
     * @return array Previous teachings' ids, in Moodle core tables.
     */
    public function find_course_previous_teaching_ids_core_tables($currentcourseid, $currentyear) {
        global $DB;

        $sql = 'SELECT prev_courses.id        AS courseid,
                       prev_courses.startdate AS starttimestamp
                FROM   {course} cur_course
                INNER JOIN {course} prev_courses
                    ON cur_course.fullname = prev_courses.fullname
                WHERE  cur_course.id = ?
                    AND prev_courses.id <> ?';
        $previouscoursesids = array();
        $recordset = $DB->get_recordset_sql($sql, array($currentcourseid, $currentcourseid));

        foreach ($recordset as $record) {
            $year = getdate($record->starttimestamp)['year'];
            if ($year < $currentyear) {
                array_push($previouscoursesids, $record->courseid);
            }
        }

        $recordset->close();

        return $previouscoursesids;
    }

    /**
     * This function finds, for the given current coures id, the same course but in previous teachings, in plugin's historic tables.
     * For that, the function looks up into the {block_mycourse_hist_course} table, finding courses with the same full name.
     * So, if it is wanted to generate recommendations for the given current course, an historic course must exist
     * in the mentioned table, so, the data importation has to be done before.
     *
     * @param int $currentcourseid The id of the current course.
     * @param int $currentyear The year the current course is being teached in.
     * @return array Previous teachings' ids, found in plugin's historic tables.
     */
    public function find_course_previous_teachings_ids_historic_tables($currentcourseid, $currentyear) {
        global $DB;

        $sql = 'SELECT historic_courses.id        AS courseid,
                       historic_courses.startdate AS starttimestamp
                FROM   {course} current_courses
                INNER JOIN {block_mycourse_hist_course} historic_courses
                    ON current_courses.fullname = historic_courses.fullname
                WHERE  current_courses.id = ?';

        $previouscoursesids = array();
        $recordset = $DB->get_recordset_sql($sql, array($currentcourseid));

        foreach ($recordset as $record) {
            $year = getdate($record->starttimestamp)['year'];
            if ($year < $currentyear) {
                array_push($previouscoursesids, $record->courseid);
            }
        }

        $recordset->close();

        return $previouscoursesids;
    }

    /**
     * Queries the number of students that the current course has had in previous teachings, in Moodle's core tables.
     *
     * @param int $currentcourseid The id of the current course.
     * @param int $currentyear The year the current course is being teached in.
     * @return int The number of students that the course has had in past teachings, found in core tables.
     */
    public function get_previous_courses_students_number_core_tables($currentcourseid, $currentyear) {
        global $DB;

        $sql = 'SELECT count(*) as count
                FROM   {user} users
                INNER JOIN {role_assignments} ra
                    ON users.id = ra.userid
                INNER JOIN {context} context
                    ON ra.contextid = context.id
                INNER JOIN {course} course
                    ON context.instanceid = course.id
                WHERE  context.contextlevel = 50
                    AND ra.roleid = 5
                    AND course.id = ?';

        $previouscourses = $this->find_course_previous_teaching_ids_core_tables($currentcourseid, $currentyear);
        $count = 0;

        if (!empty($previouscourses)) {
            foreach ($previouscourses as $course) {
                $record = $DB->get_record_sql($sql, array($course));
                $count += $record->count;
            }
        }

        return $count;
    }

    /**
     * Queries the number of students that the current course has had in previous teachings, in plugin's historic tables.
     *
     * @param int $currentcourseid The id of the current course.
     * @param int $currentyear The year the current course is being teached in.
     * @return int The number of students that the course has had in past teachings, found in plugin's historic tables.
     */
    public function get_previous_courses_students_number_historic_tables($currentcourseid, $currentyear) {
        global $DB;

        $sql = 'SELECT count(*) as count
                FROM   {block_mycourse_hist_enrol} historic_users
                WHERE  historic_users.courseid = ?';

        $previouscourses = $this->find_course_previous_teachings_ids_historic_tables($currentcourseid, $currentyear);

        $count = 0;

        if (!empty($previouscourses)) {
            foreach ($previouscourses as $course) {
                $record = $DB->get_record_sql($sql, array($course));
                $count += $record->count;
            }
        }

        return $count;
    }

    /**
     * Queries the number of resources that the current course had in previous teachings. To make the relation, the
     * name of the resource is used, since it is the unique strategy to find relation between resources.
     *
     * @param int $currentcourseid The id of the current course.
     * @param int $currentyear The year the current course is being teached in.
     * @return int The number of resources found in core tables.
     */
    public function get_previous_courses_resources_number_core_tables($currentcourseid, $currentyear) {
        global $DB;

        $sql = "SELECT count(*) AS count
                FROM   {course_modules} c_modules
                INNER JOIN {modules} modules
                    ON c_modules.module = modules.id
                WHERE  c_modules.course = ?
                    AND (modules.name = 'label'
                    OR modules.name = 'resource'
                    OR modules.name = 'folder'
                    OR modules.name = 'page'
                    OR modules.name = 'book'
                    OR modules.name = 'url')";

        $previouscourses = $this->find_course_previous_teaching_ids_core_tables($currentcourseid, $currentyear);
        $count = 0;

        if (!empty($previouscourses)) {
            foreach ($previouscourses as $course) {
                $record = $DB->get_record_sql($sql, array($course));
                $count += $record->count;
            }
        }

        return $count;
    }

    /**
     * Queries the number of resources that the current course had in previous teachings, in plugin's historic tables.
     *
     * @param int $currentcourseid The id of the current course.
     * @param int $currentyear The year the current course is being teached in.
     * @return int The number of resources found in plugin's historic tables.
     */
    public function get_previous_courses_resources_number_historic_tables($currentcourseid, $currentyear) {
        global $DB;

        $sql = 'SELECT count(distinct(historic.resourcename)) AS count
                FROM   {block_mycourse_hist_data} historic
                WHERE  historic.courseid = ?';

        $previouscourses = $this->find_course_previous_teachings_ids_historic_tables($currentcourseid, $currentyear);

        $count = 0;

        if (!empty($previouscourses)) {
            foreach ($previouscourses as $course) {
                $record = $DB->get_record_sql($sql, array($course));
                $count += $record->count;
            }
        }

        return $count;
    }

    /**
     * Calculates the duration in weeks for the given course.
     *
     * @param int $currentcourseid The id of the current course.
     * @param int $currentyear The year the current course is being teached in.
     * @return int The course duration in weeks.
     */
    public function get_course_duration_in_weeks($currentcourseid, $currentyear) {

    }

    /**
     * Queries the users selected for receiving recommendations of a course.
     *
     * @param int $courseid The course to query the users from.
     * @return array The ids of the users of the given course.
     */
    public function get_selected_users($courseid) {
        global $DB;

        $sql = 'SELECT userid
                FROM   {block_mycourse_user_sel} course
                WHERE  course.courseid = ?';

        $users = array();

        $recordset = $DB->get_recordset_sql($sql, array($courseid));

        foreach ($recordset as $record) {
            array_push($users, $record->userid);
        }

        $recordset->close();

        return $users;
    }

    /**
     * Queries the week and year start of a course.
     *
     * @param int $courseid The course to query the start week and year of.
     * @return array The week number ([1, 52]); the year.
     */
    public function get_course_start_week_and_year($courseid, $historiccourse = false) {
        global $DB;

        $coursetable = '{course}';

        if ($historiccourse) {
            $coursetable = '{block_mycourse_hist_course}';
        }

        $sql = "SELECT *
                FROM   $coursetable course
                WHERE  course.id = ?";

        $record = $DB->get_record_sql($sql, array($courseid));

        $week = date('W', $record->startdate);
        $year = date('Y', $record->startdate);

        $weekandyear = array();
        $weekandyear['week'] = intval($week);
        $weekandyear['year'] = intval($year);

        return $weekandyear;
    }

    /**
     * Quieres the associations calculated for a course in a specific week.
     *
     * @param int $courseid The course to query the associations of.
     * @param int $week The week to query the associations in.
     * @return array An object for each row, with the attributes of the queried columns.
     */
    public function get_associations($courseid, $week) {
        global $DB;

        $sql = 'SELECT associations.id,
                       associations.current_userid,
                       associations.historic_userid,
                       associations.historic_courseid
                FROM   {block_mycourse_assoc} associations
                WHERE  associations.current_courseid = ?
                AND    associations.week = ?';

        $records = $DB->get_records_sql($sql, array($courseid, $week));

        return $records;
    }

    /**
     * Queries the recommendations calculated for a course in a specific week.
     *
     * @param int $courseid The course to query the associations of.
     * @param int $userid The user receiving the recommendations.
     * @param int $week The week to query the associations in.
     * @return array An object for each row, with the attributes of the queried columns.
     */
    public function get_recommendations($courseid, $userid, $week) {
        global $DB;

        $sql = 'SELECT recommendations.id,
                       recommendations.resourceid,
                       recommendations.priority
                FROM   {block_mycourse_recs} recommendations
                INNER JOIN {block_mycourse_assoc} associations
                    ON recommendations.associationid = associations.id
                WHERE  associations.current_courseid = ?
                    AND associations.week = ?
                    AND associations.current_userid = ?
                ORDER BY recommendations.priority ASC';

        $records = $DB->get_records_sql($sql, array($courseid, $week, $userid));

        return $records;
    }

    /**
     * Queries if it is the first time that the block is loaded, necessary to know if it has to pass the course filter,
     * or if it has to display the recommendations.
     *
     * @param int $courseid The course to check if is selected.
     * @return boolean If the course is selected or not.
     */
    public function is_blocks_first_instance($courseid) {
        global $DB;

        $sql = 'SELECT count(*) as c
                FROM   {block_mycourse_course_sel} course_sel
                WHERE  course_sel.courseid = ?';

        $count = $DB->get_record_sql($sql, array($courseid));
        $count = intval($count->c);

        $firstinstance = ($count === 0) ? true : false;

        return $firstinstance;
    }

    /**
     * Queries if the given course is registered as personalizable, or not.
     *
     * @param int $courseid The course to check if is personalizable or not.
     * @return boolean If the course is personalizable or not.
     */
    public function is_course_personalizable($courseid) {
        global $DB;

        $sql = 'SELECT personalizable
                FROM   {block_mycourse_course_sel} course
                WHERE  course.courseid = ?';

        $field = $DB->get_field_sql($sql, array($courseid));

        if ($field !== null) {
            $personalizable = (intval($field) === 1) ? true : false;
        } else {
            $personalizable = false;
        }

        return $personalizable;
    }

    /**
     * Queries if the given course is registered as active to receive the recommendations, or not.
     *
     * @param int $courseid The course to check if is active or not.
     * @return boolean If the course is active or not.
     */
    public function is_course_active($courseid) {
        global $DB;

        $sql = 'SELECT active
                FROM   {block_mycourse_course_sel} course
                WHERE  course.courseid = ?';

        $field = $DB->get_field_sql($sql, array($courseid));

        if ($field !== null) {
            $personalizable = (intval($field) === 1) ? true : false;
        } else {
            $personalizable = false;
        }

        return $personalizable;
    }

    /**
     * Queries if the given user is selected, for the given course, to receive the recommendations.
     *
     * @param int $userid The user to query if is selected or not.
     * @param int $courseid The course to query the $userid is selected or not at.
     * @return boolean If the user is selected or not.
     */
    public function is_user_selected_for_course($userid, $courseid) {
        global $DB;

        $exists = $DB->record_exists('block_mycourse_user_sel', array('userid' => $userid, 'courseid' => $courseid));

        return $exists;
    }

    /**
     * Finds the id of a resource type by its name ('page', 'forum', etc.).
     *
     * @param string $typename Resource type name.
     * @return int Resource type id.
     */
    public function get_module_type_id_by_name($typename) {
        global $DB;

        $moduletype = $DB->get_record('modules', array('name' => $typename));

        return $moduletype->id;
    }

    /**
     * Finds the module id for the given course, module type and resource instance.
     *
     * @param int $courseid The course the module belongs to.
     * @param int $instance Resource instance.
     * @param int $type Module type.
     * @return int Module id.
     */
    public function get_module_id($courseid, $instance, $type) {
        global $DB;

        $module = $DB->get_record('course_modules', array('course' => $courseid, 'module' => $type, 'instance' => $instance));

        return $module->id;
    }

    /**
     * Inserts the similarity coefficient between a current user (selected user for receiving recommendations) and
     * a historic user, for the given week.
     *
     * @param int $currentuserid The current user, the one who will receive recommendations later.
     * @param int $historicuserid The historic user, the candidate to be reference for creating recommendations for
     * the current user.
     * @param float $coefficient The similarity coefficient between the two users.
     * @param int $week The week the coefficient has been calculated at.
     */
    public function insert_similarity($currentuserid, $historicuserid, $coefficient, $week) {
        global $DB;

        $record = new stdClass();
        $record->current_userid = $currentuserid;
        $record->historic_userid = $historicuserid;
        $record->coefficient = $coefficient;
        $record->week = $week;

        $DB->insert_record('block_mycourse_similarities', $record);
    }

    /**
     * Increments in one the number of recommendations views, when the user clicks on the recommendation.
     *
     * @param int $recommendationid The recommendation that has been followed.
     */
    public function increment_recommendation_view($recommendationid) {
        global $DB;

        $sql = 'UPDATE {block_mycourse_recs}
                SET    views = views + 1
                WHERE  id = ?';

        $DB->execute($sql, array($recommendationid));
    }

    /**
     * Inserts the association of a current course with every historic related course.
     *
     * @param int $currentcourse The current course, which will receive the recommendations.
     * @param array $historiccourses The previous course teachings.
     */
    public function insert_courses_associations($currentcourse, $historiccourses) {
        global $DB;

        $sql = 'INSERT INTO {block_mycourse_course_assoc} (current_courseid, historic_courseid)
                VALUES (:v1, :v2)';

        foreach ($historiccourses as $historiccourse) {
            $values = ['v1' => $currentcourse, 'v2' => $historiccourse];
            $DB->execute($sql, $values);
        }
    }

    /**
     * Queries the final grade assigned to an user for a course. It it doesn't have any, 0 is returned. This would not be
     * the usual case, since this function is going to be used for courses that are known to be finished.
     *
     * @param int $userid The user to query the grade of.
     * @param int $courseid The course to query the grade where.
     * @return float The final grade (0 if no grade is found).
     */
    public function get_users_course_final_grade($userid, $courseid) {
        global $DB;

        $sql = 'SELECT grades.finalgrade / 10 AS finalgrade
                FROM   {grade_grades} grades
                INNER JOIN {grade_items} g_items
                    ON grades.itemid = g_items.id
                WHERE g_items.itemtype = \'course\'
                    AND grades.userid = ?
                    AND g_items.courseid = ?';

        $record = $DB->get_record_sql($sql, array($userid, $courseid));

        $finalgrade = ($record) ? $record->finalgrade : 0;

        return $finalgrade;

    }

    /**
     * Dumps the previous given course (identifier of core {course} table) into the plugin's historic table structure,
     * i.e., the the historic course, its enrolled users, and the logview of each of these for each course.
     *
     * @param int $coursetodump The course of core tables that will be dumped into plugin's historic tables.
     */
    public function dump_previous_core_info_to_historic_tables($coursetodump) {
        global $DB;

        $usersids = $this->get_students_from_course($coursetodump);

        $enrolmentsql = 'INSERT INTO {block_mycourse_hist_enrol} (userid, courseid, grade)
                         VALUES (:v1, :v2, :v3)';
        $courseinfosql = 'SELECT fullname, shortname, startdate, idnumber, category
                          FROM   {course} course
                          WHERE  course.id = ?';

        $courseinfo = $DB->get_record_sql($courseinfosql, array($coursetodump));
        $coursehistoricid = $DB->insert_record('block_mycourse_hist_course', $courseinfo);

        foreach ($usersids as $userid) {
            $grade = $this->get_users_course_final_grade($userid, $coursetodump);
            $DB->execute($enrolmentsql, ['v1' => $userid, 'v2' => $coursehistoricid, 'v3' => $grade]);

            $this->dump_previous_courses_logview_info($coursetodump, $coursehistoricid);
        }
    }

    /**
     * Inserts the logview of each user for the given previous course into the plugin's table of historic data.
     * Tries to insert the logview. If the unique key is violated (courseid, userid, timestamp, resourceid), then,
     * looks if the record that is violating the key has more views. If it has, updates the record inserted before,
     * assuming that is more recent.
     *
     * @param int $coursetodump Course identifier in core.
     * @param int $coursehistoricid Course identifier in historic tables.
     */
    public function dump_previous_courses_logview_info($coursetodump, $coursehistoricid) {
        global $DB;

        $startweek = $this->get_course_start_week_and_year($coursetodump)['week'];
        $courseyear = $this->get_course_start_week_and_year($coursetodump)['year'];
        $toweek = $startweek + 52;

        $logviews = $this->query_data($coursetodump, $courseyear, $startweek, $toweek);

        foreach ($logviews as $logview) {
            $record = new stdClass();

            $record->courseid = $coursehistoricid;
            $record->userid = $logview->get_userid();
            $record->resourcename = $logview->get_modulename();
            $record->views = $logview->get_logviews();
            $record->resourcetype = $logview->get_moduletype();
            $record->resourceid = $logview->get_moduleid();
            $record->timecreated = $logview->get_timestamp();

            try {
                $DB->insert_record('block_mycourse_hist_data', $record);
            } catch (\Exception $e) {
                $existingrecord = $DB->get_record('block_mycourse_hist_data', array('courseid' => $record->courseid,
                    'userid' => $record->userid, 'resourceid' => $record->resourceid, 'resourcetype' => $record->resourcetype,
                    'timecreated' => $record->timecreated));

                $moreviews = $record->views > $existingrecord->views;

                if ($moreviews) {
                    $record->id = $existingrecord->id;
                    $DB->update_record('block_mycourse_hist_data', $record);
                }
            }
        }
    }

    /**
     * Initiates a database transaction. The changes made after calling this function will not be saved until
     * commit_transaction is called (with the returned object passed as argument), so, this must be used carefully.
     * The main purpose of this is to try a set of instructions (inserts into different tables, usually) that fulfill the
     * defined integrity rules, to commit the operations if they fulfill, or to rollback and not save any change if they don't
     * and an exception has been thrown.
     * In short, this is though to be used within a try/catch block, where the last instruction of the try would be the
     * commit_transaction call; and the instruction of the catch would be the rollback_transaction call.
     *
     * @return object Transaction object.
     */
    public function start_transaction() {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        return $transaction;
    }

    /**
     * Commits the operation done within the transaction. This function call should be the last instruction of the try/catch block
     * that handles the database operations.
     *
     * @param object $transaction Generated transaction.
     */
    public function commit_transaction($transaction) {
        $transaction->allow_commit();
    }

    /**
     * Rollback the operations done within the transaction, saving no changes made. This function call should be the called in the
     * catch of the try/catch block that handles the database operations.
     *
     * @param object $transaction Generated transaction.
     * @param object $exception The exception generated by an illegal database operation.
     */
    public function rollback_transaction($transaction, $exception) {
        $transaction->rollback($exception);
    }

    /**
     * Inserts the course read from the csv. This is being called under the same transaction of insert_historic_user_enrol and
     * insert_historic_logs.
     *
     * @param string $fullname The full name of the course.
     * @param string $shortname The short name of the course.
     * @param int $startdate The start date of the course.
     * @param string $idnumber The id number of the course.
     * @param string $category The category of the course.
     * @return int Generated historic course identifier.
     */
    public function insert_historic_course($fullname, $shortname, $startdate, $idnumber, $category) {
        global $DB;

        $record = new stdClass();

        $record->fullname = $fullname;
        $record->shortname = $shortname;
        $record->startdate = $startdate;
        $record->idnumber = $idnumber;
        $record->category = $category;

        $courseid = $DB->insert_record('block_mycourse_hist_course', $record);

        return $courseid;
    }

    /**
     * Inserts the user read from the csv (in a loop, for each row of the csv), with the course identifier generated in
     * insert_historic_course. This is being called within the same transaction of insert_historic_course and insert_historic_logs.
     *
     * @param int $userid The user id.
     * @param float $grade The final grade the user obtained in the course.
     * @param int $courseid The course the user is enrolled in, generated in insert_historic_course.
     */
    public function insert_historic_user_enrol($userid, $grade, $courseid) {
        global $DB;

        $sql = 'INSERT INTO {block_mycourse_hist_enrol} (userid, grade, courseid)
                VALUES (:v1, :v2, :v3)';
        $values = ['v1' => $userid, 'v2' => $grade, 'v3' => $courseid];

        $DB->execute($sql, $values);
    }

    /**
     * Inserts the logs read from the csv (in a loop, for each row of the csv), with the course identifier generated in
     * insert_historic_course. This is being called within the same transaction of insert_historic_course and insert_historic_logs.
     *
     * @param int $userid The user that generated the log view.
     * @param int $courseid The course the user is enrolled in for this log view, generated in insert_historic_course.
     * @param string $resourcename The name of the resource that the user viewed.
     * @param string $resourcetype The resource type of the viewed resource.
     * @param int $resourceid The id of the viewed resource.
     * @param int $views The number of views of the resource.
     * @param int $timecreated The time the resource was viewed at. This is being trunked to the first day of the week the resource
     * was viewed at, since the log views are currently saved by week.
     */
    public function insert_historic_logs($userid, $courseid, $resourcename, $resourcetype, $resourceid, $views, $timecreated) {
        global $DB;

        $record = new stdClass();

        $record->userid = $userid;
        $record->courseid = $courseid;
        $record->resourcename = $resourcename;
        $record->resourcetype = $resourcetype;
        $record->resourceid = $resourceid;
        $record->views = $views;
        $record->timecreated = $timecreated;

        $DB->insert_record('block_mycourse_hist_data', $record);
    }

    /**
     * Checks if the data has to be imported from core tables for the given course. To check this, looks for the associations
     * table; if there is no row for the given course, means that the cron task has no been executed for this course before to
     * create the recommendations, so, the data has to be imported. The next time, the given course would have at least a row
     * int associations table, meaning that that a data import has been done.
     *
     * @param int $courseid The course to check if needs to have the importation.
     * @return boolean If the data for the given course has to be imported or not.
     */
    public function has_data_to_be_imported($courseid) {
        global $DB;

        $count = $DB->count_records('block_mycourse_assoc', array('current_courseid' => $courseid));

        $importdata = ($count === 0) ? true : false;

        return $importdata;
    }

    /**
     * Establishes the relation between a current course and its historic. Called after the importation of csv data.
     *
     * @param int $currentcourse The current course for the association is being creating for.
     * @param int $historiccourse The historic course the current course is associated with.
     */
    public function associate_current_course_with_historic($currentcourse, $historiccourse) {
        global $DB;

        $sql = 'INSERT INTO {block_mycourse_course_assoc} (current_courseid, historic_courseid)
                VALUES (:v1, :v2)';
        $values = ['v1' => $currentcourse, 'v2' => $historiccourse];

        $DB->execute($sql, $values);
    }
}

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
 * Defines restore_zatuk_activity_task class
 *
 * @since Moodle 2.0
 * @package    mod_zatuk
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/zatuk/backup/moodle2/restore_zatuk_stepslib.php'); // Because it exists (must).

/**
 * content restore task that provides all the settings and steps to perform one
 * complete restore of the activity
 */
class restore_zatuk_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Label only has one structure step.
        $this->add_step(new restore_zatuk_activity_structure_step('zatuk_structure', 'zatuk.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents() {
        $contents = [];

        $contents[] = new restore_decode_content('zatuk', ['externalurl'], 'zatuk');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('CONTENTVIEWBYID', '/mod/zatuk/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('CONTENTINDEX', '/mod/zatuk/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * page logs. It must return one array
     */
    public static function define_restore_log_rules() {
        $rules = [];

        $rules[] = new restore_log_rule('zatuk', 'add', 'view.php?id={course_module}', '{zatuk}');
        $rules[] = new restore_log_rule('zatuk', 'update', 'view.php?id={course_module}', '{zatuk}');
        $rules[] = new restore_log_rule('zatuk', 'view', 'view.php?id={course_module}', '{zatuk}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * course logs. It must return one array
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    public static function define_restore_log_rules_for_course() {
        $rules = [];

        $rules[] = new restore_log_rule('zatuk', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}

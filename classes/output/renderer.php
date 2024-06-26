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
 * mod_zatuk renderer class
 *
 * @since Moodle 2.0
 * @package    mod_zatuk
 * @copyright  2023 Moodle India
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_zatuk\output;
defined('MOODLE_INTERNAL') || die;
use mod_zatuk\local\filters as filters;
use html_writer;
use user_course_details;
use html_table;
use moodle_url;

require_once($CFG->dirroot.'/mod/zatuk/locallib.php');
/**
 * class filters
 */
class renderer extends \plugin_renderer_base {

    /**
     * Defer to template.
     *
     * @param coursestats $page
     * @return string html for the page
     */
    public function render_player($page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('mod_zatuk/player', $data);
    }
    /**
     * function uploadedvideos
     *
     */
    public function uploadedvideos() {
        $condition = ['tableid' => 'zatuk_uploaded_videos_data', 'function' => 'zatuk_uploaded_videos_data'];
        return $this->render_from_template('mod_zatuk/zatukVideos', $condition);
    }
    /**
     * function zatukvideos
     *
     */
    public function zatukvideos() {
        $condition = ['tableid' => 'get_zatuk_data', 'function' => 'get_zatuk_data'];
        return $this->render_from_template('mod_zatuk/zatukVideos', $condition);
    }

    /**
     * function coursestats
     * @param string||null $courseid
     * @param string||null $params
     * @param string||null $search
     */
    public function coursestats($courseid = null, $params = null, $search = null) {
        $coursevideossql = "SELECT cm.course, c.fullname,
         COUNT(s.name) AS totalvideos, COUNT(cm.id) AS activevideos FROM {zatuk} s
            JOIN {course_modules} cm ON cm.instance = s.id
            AND cm.module = (SELECT id FROM {modules} WHERE name = 'zatuk')
            JOIN {course} c ON c.id = cm.course WHERE cm.visible = '1'";
        if (!is_null($courseid) && $courseid > 1) {
            $coursevideossql .= " AND cm.course = {$courseid} ";
        }
        if ($search != '') {
            $sql .= " AND c.fullname LIKE '%". $search ."%' ";
        }
        $coursevideossql .= " GROUP BY cm.course";
        $coursescount = count($this->db->get_records_sql($coursevideossql));
        $courses = $this->db->get_records_sql($coursevideossql, [], $params["start"], $params["length"]);
        return compact('courses', 'coursescount');
    }
    /**
     * function viewsinfo
     * @param array $viewsdata
     * @param array $params
     */
    public function viewsinfo($viewsdata, $params) {
        $viewsinfo = $viewsdata['views'];
        $data = [];
        foreach ($viewsinfo as $viewinfo) {
            $row = [];
            $row[] = $viewinfo->fullname;
            $row[] = $viewinfo->attempts;
            $row[] = date('jS F Y', $viewinfo->timecreated);
            $data[] = $row;
        }
        $itotal = $viewsdata['viewscount'];
        $outputs = [
                "draw" => isset($params['draw']) ? intval($params['draw']) + 1 : 1,
                "iTotalRecords" => $itotal,
                "iTotalDisplayRecords" => $itotal,
                "data" => json_encode($data, true),
            ];
        return $outputs;
    }
    /**
     * function zatukrender
     * @param array $zatukinfo
     * @param array $params
     */
    public function zatukrender($zatukinfo, $params) {
        $content = $zatukinfo['returndata'];
        $total = $zatukinfo['total'];
        $data = [];
        foreach ($content as $zatuk) {
            $data[] = [$this->render_from_template('mod_zatuk/videocard', $zatuk)];
        }
        $outputs = [
            "draw" => isset($params['draw']) ? intval($params['draw']) + 1 : 1,
            "iTotalRecords" => $total,
            "iTotalDisplayRecords" => $total,
            "data" => json_encode($data, true),
        ];
        return $outputs;
    }
    /**
     * function uploadrender
     * @param array $uploaddata
     * @param array $params
     */
    public function uploadrender($uploaddata, $params) {
        global $DB;
        $content = $uploaddata['content'];
        $total = $uploaddata['total'];
        $tdata = [];
        foreach ($content as $video) {
            $data = [];
            $data['title'] = $video->title;
            $data['tagsname'] = $video->tagsname;
            $thumbnaillogourl = $this->get_thumbnail_url();
            $data['thumbnail'] = html_writer::tag('img', '', ["src" => $thumbnaillogourl, 'height' => '100px', 'width' => '100px']);
            $data['username'] = $video->userfullname;
            $data['timecreated'] = date('d M Y', $video->timecreated);
            $data['status'] = $video->status == 0 ? get_string('notsynced', 'zatuk') :
             get_string('syncedat', 'zatuk').date('d M Y', $video->uploaded_on);
            $tdata[] = [$this->render_from_template('mod_zatuk/videocard', $data)];
        }
        $outputs = [
            "draw" => isset($params['draw']) ? intval($params['draw']) + 1 : 1,
            "iTotalRecords" => $total,
            "iTotalDisplayRecords" => $total,
            "data" => json_encode($tdata, true),
        ];
        return $outputs;
    }
    /**
     * function render_uploadedvideos
     * @param stdclass $output  // uploadedvideos
     */
    public function render_uploadedvideos($output) {

        return $this->render_from_template('mod_zatuk/list', $output->export_for_template($this));

    }
    /**
     * function get_thumbnail_url
     * @param int $logoitemid
     */
    public function get_thumbnail_url() {
        global $DB;
        $thumbnaillogourl = '';
        $thumbnaillogourl = $this->image_url('video', 'mod_zatuk');
        return $thumbnaillogourl;
    }
    /**
     * function handleemptyelements
     * @param array $data
     * @param int $count
     */
    public function handleemptyelements($data, $count) {
        if (count($data) == $count) {
            $lists = [];
        } else {
             $lists = range(count($data) - 1, $count);
        }
        $returndata = $data;
        foreach ($lists as $list) {
            $returndata[] = null;
        }
        return $returndata;
    }
}

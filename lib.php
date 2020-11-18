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
 * Plugin administration pages are defined here.
 *
 * @package     local_qsearchbytype
 * @category    admin
 * @copyright   2020 Chandra Kishor <developerck@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->dirroot . '/question/editlib.php');

/**
 * Callback of Filter
 *
 * @param $customview object object of view that is calling this function
 * @return array of object of the class the implement the functionality
 */
function local_qsearchbytype_get_question_bank_search_conditions($customview) {
    return array(new local_qsearchbytype_question_bank_search_condition($customview));
}

/**
 * Implementing search
 *
 */
class local_qsearchbytype_question_bank_search_condition extends core_question\bank\search\condition {

    /**
     *
     * @var string
     */
    protected $questype;

    /**
     *
     * @var string
     */
    protected $where;

    /**
     *
     * @var array
     */
    protected $params;

    public function __construct($customview) {
        $this->questype = optional_param('questype', '', PARAM_TEXT);
        if (!$this->questype) {
            // To parse the parameter from json request.
            $arguments = file_get_contents('php://input');
            $requests = json_decode($arguments, true);
            if (!empty($requests)) {
                $args = $requests[0]['args']['args'][0];
                $querystring = preg_replace('/^\?/', '', $args['value']);
                $params = [];
                parse_str($querystring, $params);
                if (!empty($params['questype'])) {
                    $this->questype = $params['questype'];
                }
            }
        }

        if ((!empty($this->questype))) {
            // Intializing of there is value.
            $this->init();
        }
    }

    /**
     * Abstract method implementation
     *
     * @return string sql condtion
     */
    public function where() {
        return $this->where;
    }

    /**
     * Abstract method implementation
     *
     * @return array
     */
    public function params() {
        return $this->params;
    }

    /**
     * Html view of drop down
     *
     *
     */
    public function display_options() {
        echo \html_writer::start_div('qsearchbytype');
        $questiontypes = $this->get_types();
        $questiontypes[''] = get_string('default', 'local_qsearchbytype');
        echo \html_writer::label(get_string('types', 'local_qsearchbytype'), 'id_qsearchbytype', true, array("class" => "mr-1"));
        echo \html_writer::select($questiontypes, 'questype', $this->questype, array(),
                array('class' => 'searchoptions custom-select', 'id' => 'id_qsearchbytype'));
        echo \html_writer::end_div() . "\n";
    }

    /**
     * Settign up the condtions
     *
     * @global type $DB
     */
    private function init() {
        global $DB;
        $this->params = array();
        if (!empty($this->questype)) {
            if (!empty($this->where)) {
                $this->where .= " AND ";
            }
            if (!is_numeric($this->questype)) {
                $objectiveids = array();
                list($where, $parasm2) = $DB->get_in_or_equal($this->questype, SQL_PARAMS_NAMED, 'questype');

                $this->where .= " q.qtype $where";
                $this->params = array_merge($this->params, $parasm2);
            }
        }
    }

    /**
     * get_type
     *
     * @global type $DB
     * @return array array of enabled question type
     */
    private function get_types() {
        global $DB;
        foreach (question_bank::get_creatable_qtypes() as $qtypename => $qtype) {
            $realqtypes[$qtypename] = $qtype->local_name();
        }
        return $realqtypes;
    }

}

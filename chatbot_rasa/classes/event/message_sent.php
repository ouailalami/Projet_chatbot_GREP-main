<?php
/**
 * {@link https://docs.moodle.org/dev/Events_API}
 * {@link https://docs.moodle.org/dev/Migrating_logging_calls_in_plugins#.22Installation.22_of_events}
 */
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
 * The message_sent event.
 *
 * @package    chatbot_rasa
 * @copyright  2021 José-Paul Blülle Celado
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_chatbot_rasa\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The message_sent event class.
 *
 * @property-read array $other {
 *      Extra information about event.
 *
 *      PAS UTILISE POUR L'INSTANT.
 *      Car déjà dans une base de données (juste pas les erreurs loguées).
 * }
 *
 * @since     Moodle MOODLEVERSION
 * @copyright 2021 José-Paul Blülle Celado
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
class message_sent extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'block_chatbot_rasa_interact';
    }

    public static function get_name() {
        return get_string('event_name_message_sent', 'block_chatbot_rasa');
    }

    public function get_description() {
        return "L'utilisateur avec l'id '{$this->userid}' a enovyé un message à Rasa avec l'id '{$this->objectid}' pour le cours '{$this->courseid}'.";// get_string('event_description_message_sent', 'block_chatbot_rasa');
    }
}

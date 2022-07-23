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
 * Installation code for the whatsapp message processor
 *
 * @package    message_whatsapp
 * @copyright  2022 Abhishek Kumar
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Install the email message processor
 */
function xmldb_message_whatsapp_install() {
    global $DB;
    $result = true;

    $provider = new stdClass();
    $provider->name  = 'whatsapp';
    $DB->insert_record('message_processors', $provider);
    return $result;
}

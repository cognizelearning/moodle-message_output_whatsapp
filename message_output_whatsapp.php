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
 * Contains the definiton of the whatsapp message processors (sends messages to users via whatsapp)
 *
 * @package   message_whatsapp
 * @copyright  @copyright  2022 Cognize Learning
 * @author     Abhishek Kumar <abhishek@cognizelearning.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;
require_once($CFG->dirroot . '/message/output/lib.php');

require_once $CFG->dirroot . '/vendor/autoload.php';

use Twilio\Rest\Client;

class message_output_whatsapp extends message_output {

    /**
     * Processes the message (sends by whatsapp).
     * @param object $eventdata the event data submitted by the message sender plus $eventdata->savedmessageid
     */
    public function send_message($eventdata) {
        global $CFG, $DB;

        $config = get_config('message_whatsapp');

        if (empty($config->authtoken) || empty($config->accountsid)) {
            return true;
        }

        if (!empty($CFG->noemailever)) {
            // Hidden setting for development sites, set in config.php if needed.
            debugging('$CFG->noemailever active, no airnotifier message sent.', DEBUG_MINIMAL);
            return true;
        }
        // Skip any messaging suspended and deleted users.
        if ($eventdata->userto->auth === 'nologin' or $eventdata->userto->suspended or $eventdata->userto->deleted) {
            return true;
        }

        // The user the whatsapp is going to.
        $recipient = null;

        // Check if the recipient has a different whatsapp address specified in their messaging preferences Vs their user profile.
        $whatsappmessagingpreference = get_user_preferences('message_processor_whatsapp_whatsapp', null, $eventdata->userto);
        $whatsappmessagingpreference = clean_param($whatsappmessagingpreference, PARAM_ALPHANUMEXT);

        // If the recipient has set an whatsapp address in their preferences use that instead of the one in their profile
        // But only if overriding the notification whatsapp address is allowed.
        if (!empty($whatsappmessagingpreference) && !empty($CFG->messagingallowwhatsappoverride)) {
            // Clone to avoid altering the actual user object.
            $recipient = clone($eventdata->userto);
            $recipient->whatsapp = $whatsappmessagingpreference;
        } else {
            $recipient = $eventdata->userto;
        }

        // Configure mail replies - this is used for incoming mail replies.
        $replyto = '';
        $replytoname = '';
        if (isset($eventdata->replyto)) {
            $replyto = $eventdata->replyto;
            if (isset($eventdata->replytoname)) {
                $replytoname = $eventdata->replytoname;
            }
        }

        // We whatsapp messages from private conversations straight away, but for group we add them to a table to be sent later.
        $whatsappuser = true;
        if (!$eventdata->notification) {
            if ($eventdata->conversationtype == \core_message\api::MESSAGE_CONVERSATION_TYPE_GROUP) {
                $whatsappuser = false;
            }
        }

        $twilio = new Client($config->accountsid, $config->authtoken);

        $message = $twilio->messages->create("whatsapp:$eventdata->userto->phone1",
                array(
                    "from" => "whatsapp:$config->senderno",
                    "body" => $eventdata->fullmessage
                )
        );

        return true;
    }

    /**
     * Creates necessary fields in the messaging config form.
     *
     * @param array $preferences An array of user preferences
     */
    public function config_form($preferences) {
        return null;
    }

    /**
     * Parses the submitted form data and saves it into preferences array.
     *
     * @param stdClass $form preferences form class
     * @param array $preferences preferences array
     */
    public function process_form($form, &$preferences) {
        return true;
    }

    /**
     * Returns the default message output settings for this output
     *
     * @return int The default settings
     */
    public function get_default_messaging_settings() {
        return MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED;
    }

    /**
     * Loads the config data from database to put on the form during initial form display
     *
     * @param array $preferences preferences array
     * @param int $userid the user id
     */
    public function load_data(&$preferences, $userid) {
        $preferences->whatsapp_whatsapp = get_user_preferences('message_processor_whatsapp_whatsapp', '', $userid);
    }

    /**
     * Returns true as message can be sent to internal support user.
     *
     * @return bool
     */
    public function can_send_to_any_users() {
        return true;
    }

}

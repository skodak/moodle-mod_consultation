<?php

// This file is part of Consultation module for Moodle.
//
// Consultation is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Consultation is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Consultation.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Consultation specific functions
 *
 * @package    mod
 * @subpackage consultation
 * @copyright  2009 Petr Skoda {@link http://skodak.org}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->dirroot/mod/consultation/lib.php");

// SQL FUNCTIONS ///////////////////////////////////////////////////////////////////

/**
 * Count number of inquiries started by a give user.
 * @param object $consultation
 * @param int $userid
 * @return int
 */
function consultation_count_started_inquiries($consultation, $userid) {
    global $DB;
    return $DB->count_records('consultation_inquiries', array('consultationid'=>$consultation->id, 'userfrom'=>$userid));
}

/**
 * Load user info
 * @param array $userids
 * @return array of object
 */
function consultation_load_users($userids) {
    global $DB;

    if (empty($userids)) {
        return array();
    }
    $userids = (array)$userids;
    $chunks = array_chunk($userids, 50);
    $result = array();
    foreach ($chunks as $chunk) {
        list($insql, $params) = $DB->get_in_or_equal($chunk);
        if ($users = $DB->get_records_select('user', "id $insql", $params, 'lastname, firstname', 'id, username, firstname, lastname, picture, imagealt, idnumber, email')) {
            if ($result) {
                foreach ($users as $key=>$user) {
                    $result[$key] = $user;
                }
            } else {
                $result = $users;
            }
        }
    }
    return $result;
}

/**
 * Get list of all open inquiries user is not participating in
 * @param object $consultation
 * @param int $userid
 * @param int $page
 * @param int $perpage
 * @param string $orderby
 * @return array (count, array of inquiries)
 */
function consultation_get_others_open_inquiries($consultation, $userid, $page, $perpage, $orderby) {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {consultation_inquiries} c
              JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = :cid AND c.resolved = 0 AND uf.id <> :u1 AND ut.id <> :u2";
    if (!$inquirycount = $DB->count_records_sql($sql, array('cid'=>$consultation->id, 'u1'=>$userid, 'u2'=>$userid))) {
        return array(0, array());
    }
    $sql = "SELECT c.id, c.userfrom, c.userto, c.subject, c.resolved, c.timecreated, c.timemodified,
                   (SELECT COUNT('x') FROM {consultation_posts} e2 WHERE e2.inquiryid = c.id) AS total
              FROM {consultation_inquiries} c
              JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = :cid AND c.resolved = 0 AND uf.id <> :u1 AND ut.id <> :u2
          ORDER BY $orderby";
    $inquiries = $DB->get_records_sql($sql, array('cid'=>$consultation->id, 'u1'=>$userid, 'u2'=>$userid), $page*$perpage, $perpage);
    return array($inquirycount, $inquiries);
}

/**
 * Get list of all resolved inquiries user is not participating in
 * @param object $consultation
 * @param int $userid
 * @param int $page
 * @param int $perpage
 * @param string $orderby
 * @return array (count, array of inquiries)
 */
function consultation_get_others_resolved_inquiries($consultation, $userid, $page, $perpage, $orderby) {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {consultation_inquiries} c
              JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = :cid AND c.resolved = 1 AND uf.id <> :u1 AND ut.id <> :u2";
    if (!$inquirycount = $DB->count_records_sql($sql, array('cid'=>$consultation->id, 'u1'=>$userid, 'u2'=>$userid))) {
        return array(0, array());
    }
    $sql = "SELECT c.id, c.userfrom, c.userto, c.subject, c.resolved, c.timecreated, c.timemodified,
                   (SELECT COUNT('x') FROM {consultation_posts} e2 WHERE e2.inquiryid = c.id) AS total
              FROM {consultation_inquiries} c
              JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = :cid AND c.resolved = 1 AND uf.id <> :u1 AND ut.id <> :u2
          ORDER BY $orderby";
    $inquiries = $DB->get_records_sql($sql, array('cid'=>$consultation->id, 'u1'=>$userid, 'u2'=>$userid), $page*$perpage, $perpage);
    return array($inquirycount, $inquiries);
}

/**
 * Get list of all open inquiries user is participating in
 * @param object $consultation
 * @param int $userid
 * @param int $page
 * @param int $perpage
 * @param string $orderby
 * @return array (count, array of inquiries)
 */
function consultation_get_my_open_inquiries($consultation, $userid, $page, $perpage, $orderby) {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {consultation_inquiries} c
              JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = :cid AND c.resolved = 0 AND (uf.id = :u1 OR ut.id = :u2)";
    if (!$inquirycount = $DB->count_records_sql($sql, array('cid'=>$consultation->id, 'u1'=>$userid, 'u2'=>$userid))) {
        return array(0, array());
    }
    $userid = (int)$userid; //TODO: no idea if WHEN supports bound params
    $sql = "SELECT c.id, c.userwith, c.subject, c.resolved, c.timecreated, c.timemodified,
                   (SELECT COUNT('x') FROM {consultation_posts} e1 WHERE e1.inquiryid = c.id AND e1.seenon IS NULL AND userid <> :u0) AS unread,
                   (SELECT COUNT('x') FROM {consultation_posts} e2 WHERE e2.inquiryid = c.id) AS total
              FROM (SELECT cx.*, CASE cx.userfrom WHEN $userid THEN cx.userto ELSE cx.userfrom END AS userwith
                     FROM {consultation_inquiries} cx
                     JOIN {user} uf ON (uf.id = cx.userfrom AND uf.deleted = 0)
                     JOIN {user} ut ON (ut.id = cx.userto AND ut.deleted = 0)
                    WHERE cx.consultationid = :cid AND cx.resolved = 0 AND (uf.id = :u1 OR ut.id = :u2)
                   ) c
              JOIN {user} u ON (u.id = c.userwith)
          ORDER BY $orderby";
    $inquiries = $DB->get_records_sql($sql, array('cid'=>$consultation->id, 'u0'=>$userid, 'u1'=>$userid, 'u2'=>$userid), $page*$perpage, $perpage);
    return array($inquirycount, $inquiries);
}

/**
 * Get list of all unread inquiries user is participating in
 * @param object $consultation
 * @param int $userid
 * @param int $page
 * @param int $perpage
 * @param string $orderby
 * @return array (count, array of inquiries)
 */
function consultation_get_my_unread_inquiries($consultation, $userid, $page, $perpage, $orderby) {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {consultation_inquiries} c
              JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = :cid AND (uf.id = :u0 OR ut.id = :u1)
                   AND EXISTS(SELECT 'x'
                                FROM {consultation_posts} e3
                               WHERE e3.inquiryid = c.id AND e3.seenon IS NULL AND e3.userid <> :u2)";
    if (!$inquirycount = $DB->count_records_sql($sql, array('cid'=>$consultation->id, 'u0'=>$userid, 'u1'=>$userid, 'u2'=>$userid))) {
        return array(0, array());
    }
    $userid = (int)$userid; //TODO: no idea if WHEN supports bound params
    $sql = "SELECT c.id, c.userwith, c.subject, c.resolved, c.timecreated, c.timemodified,
                   (SELECT COUNT('x') FROM {consultation_posts} e1 WHERE e1.inquiryid = c.id AND e1.seenon IS NULL AND e1.userid <> :u0) AS unread,
                   (SELECT COUNT('x') FROM {consultation_posts} e2 WHERE e2.inquiryid = c.id) AS total
              FROM (SELECT cx.*, CASE cx.userfrom WHEN $userid THEN cx.userto ELSE cx.userfrom END AS userwith
                     FROM {consultation_inquiries} cx
                     JOIN {user} uf ON (uf.id = cx.userfrom AND uf.deleted = 0)
                     JOIN {user} ut ON (ut.id = cx.userto AND ut.deleted = 0)
                    WHERE cx.consultationid = :cid AND (uf.id = $userid OR ut.id = :u1)
                           AND EXISTS(SELECT 'x'
                                        FROM {consultation_posts} e3
                                       WHERE e3.inquiryid = cx.id AND e3.seenon IS NULL AND e3.userid <> :u2)
                    ) c
               JOIN {user} u ON (u.id = c.userwith)
           ORDER BY $orderby";
    $inquiries = $DB->get_records_sql($sql, array('cid'=>$consultation->id, 'u0'=>$userid, 'u1'=>$userid, 'u2'=>$userid), $page*$perpage, $perpage);
    return array($inquirycount, $inquiries);
}

/**
 * Get list of all resolved inquiries user is participating in
 * @param object $consultation
 * @param int $userid
 * @param int $page
 * @param int $perpage
 * @param string $orderby
 * @return array (count, array of inquiries)
 */
function consultation_get_my_resolved_inquiries($consultation, $userid, $page, $perpage, $orderby) {
    global $DB;

    $sql = "SELECT COUNT('x')
              FROM {consultation_inquiries} c
              JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = :cid AND c.resolved = 1 AND (uf.id = :u1 OR ut.id = :u2)";
    if (!$inquirycount = $DB->count_records_sql($sql, array('cid'=>$consultation->id, 'u1'=>$userid, 'u2'=>$userid))) {
        return array(0, array());
    }
    $userid = (int)$userid; //TODO: no idea if WHEN supports bound params
    $sql = "SELECT c.id, c.userwith, c.subject, c.resolved, c.timecreated, c.timemodified,
                   (SELECT COUNT('x') FROM {consultation_posts} e1 WHERE e1.inquiryid = c.id AND e1.seenon IS NULL AND userid <> :u0) AS unread,
                   (SELECT COUNT('x') FROM {consultation_posts} e2 WHERE e2.inquiryid = c.id) AS total
              FROM (SELECT cx.*, CASE cx.userfrom WHEN $userid THEN cx.userto ELSE cx.userfrom END AS userwith
                     FROM {consultation_inquiries} cx
                     JOIN {user} uf ON (uf.id = cx.userfrom AND uf.deleted = 0)
                     JOIN {user} ut ON (ut.id = cx.userto AND ut.deleted = 0)
                    WHERE cx.consultationid = :cid AND cx.resolved = 1 AND (uf.id = :u1 OR ut.id = :u2)
                   ) c
              JOIN {user} u ON (u.id = c.userwith)
          ORDER BY $orderby";
    $inquiries = $DB->get_records_sql($sql, array('cid'=>$consultation->id, 'u0'=>$userid, 'u1'=>$userid, 'u2'=>$userid), $page*$perpage, $perpage);
    return array($inquirycount, $inquiries);
}


/**
 * Counts inquiries statistics
 * @param int $userid
 * @param int $consultationid
 * @param bool $others include counts for not participating inquiries
 * @param int $ignoreunreadin special case when viewing unread inquiry
 * @return object with counts
 */
function consultation_get_counts($userid, $consultationid, $others=false, $ignoreunreadin=0) {
    global $DB;

    $counts = new object();

    // my resolved consultations
    $sql = "SELECT COUNT('x')
              FROM {consultation_inquiries} c
              JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = :cid AND c.resolved = 1 AND (uf.id = :u1 OR ut.id = :u2)";
    $counts->myresolved = $DB->count_records_sql($sql, array('cid'=>$consultationid, 'u1'=>$userid, 'u2'=>$userid));

    // my open consultations
    $sql = "SELECT COUNT('x')
              FROM {consultation_inquiries} c
              JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = :cid AND c.resolved = 0 AND (uf.id = :u1 OR ut.id = :u2)";
    $counts->myopen = $DB->count_records_sql($sql, array('cid'=>$consultationid, 'u1'=>$userid, 'u2'=>$userid));

    // my unread posts - open and resolved
    $sql = "SELECT COUNT('x')
              FROM {consultation_inquiries} c
              JOIN {consultation_posts} e ON (e.inquiryid = c.id)
              JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = :cid AND (uf.id = :u1 OR ut.id = :u2)
                   AND e.userid <> :u0 AND e.seenon IS NULL";
    if ($ignoreunreadin) {
        // this is a special case for printing of inquiries,
        // the seenon flag is set immediately after display
        $sql = "$sql AND c.id <> :ignoreunreadin";
    }
    $counts->myunread = $DB->count_records_sql($sql, array('cid'=>$consultationid, 'u0'=>$userid, 'u1'=>$userid, 'u2'=>$userid, 'ignoreunreadin'=>$ignoreunreadin));

    $counts->othersresolved = null;
    $counts->othersopen = null;
    if ($others) {
        // resolved consultations of others
        $sql = "SELECT COUNT('x')
                  FROM {consultation_inquiries} c
                  JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
                  JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
                 WHERE c.consultationid = :cid AND c.resolved = 1 AND uf.id <> :u1 AND ut.id <> :u2";
        $counts->othersresolved = $DB->count_records_sql($sql, array('cid'=>$consultationid, 'u1'=>$userid, 'u2'=>$userid));

        // open consultations of others
        $sql = "SELECT COUNT('x')
                  FROM {consultation_inquiries} c
                  JOIN {user} uf ON (uf.id = c.userfrom AND uf.deleted = 0)
                  JOIN {user} ut ON (ut.id = c.userto AND ut.deleted = 0)
                 WHERE c.consultationid = :cid AND c.resolved = 0 AND uf.id <> :u1 AND ut.id <> :u2";
        $counts->othersopen = $DB->count_records_sql($sql, array('cid'=>$consultationid, 'u1'=>$userid, 'u2'=>$userid));
    }

    return $counts;
}

/**
 * Returns new consultation inquiry candidates
 * @param object $user current user
 * @param int $groupid
 * @param object $course
 * @param object $consultation
 * @param object $cm
 * @param object $course
 * @return array (array(userid=>username), array(userid=>username))
 */
function consultation_get_candidates($user, $groupid, $consultation, $cm, $course) {
    global $DB;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $caviewfull = has_capability('moodle/site:viewfullnames', $context, $user->id);
    $openany    = has_capability('mod/consultation:openany', $context, $user->id);

    $groupmode = groups_get_activity_groupmode($cm, $course);
    $separategroups = ($groupmode == SEPARATEGROUPS and !has_capability('moodle/site:accessallgroups', $context, $user->id));
    if ($separategroups and !$groupid) {
        // no users in the same group
        return array(array(), array());
    }

    if ($openany) {
        if (!$candidates = get_enrolled_users($context, '', $groupid, 'u.id,u.lastname,u.firstname', 'u.lastname ASC, u.firstname ASC')) {
            return array(array(), array());
        }

    } else {
        if (!$candidates = get_enrolled_users($context, 'mod/consultation:answer', $groupid, 'u.id,u.lastname,u.firstname', 'u.lastname ASC, u.firstname ASC')) {
            return array(array(), array());
        }
    }

    unset($candidates[$user->id]); // no self
    $guest = guest_user();
    unset($candidates[$guest->id]); // no guests

    /// make names array
    foreach ($candidates as $key=>$candidate) {
        if ($key <= 0) {
            continue;
        }
        $candidates[$key] = fullname($candidate, $caviewfull);
    }

    $candidates_existing = array();
/// now exclude already open and also multiple if not allowed
    if ($existing = $DB->get_records_select('consultation_inquiries',
                                            "consultationid = :cid  AND (userfrom = :u1 OR userto = :u2)", array('cid'=>$consultation->id, 'u1'=>$user->id, 'u2'=>$user->id),
                                            '', 'id,userfrom,userto')) {
        foreach ($existing as $conv) {
            if ($conv->userto == $user->id) {
                $uid = $conv->userfrom;
            } else {
                $uid = $conv->userto;
            }
            if (!isset($candidates[$uid])) {
                continue;
            }
            // put at the end of list
            $candidates_existing[$uid] = $candidates[$uid];
            unset($candidates[$uid]);
        }
    }

    return array($candidates, $candidates_existing);
}


// OTHER CONSULTATION FUNCTIONS ///////////////////////////////////////////////////////////////////

/**
 * Print one inquiry
 * @param object $inquiry
 * @param object $consultation
 * @param object $cm
 * @param object $course
 * @param string $baseurl page url, needed for paging
 * @param array $urlparams url parameters added to $baseurl
 * @param bool $fullview false when printing overview only
 * @return void
 */
function consultation_print_inquiry($inquiry, $consultation, $cm, $course, $baseurl, $urlparams, $fullview=true) {
    global $CFG, $USER, $DB, $OUTPUT;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $caviewfull   = has_capability('moodle/site:viewfullnames', $context);
    $candeleteany = has_capability('mod/consultation:deleteany', $context);
    $canresolve   = (has_capability('mod/consultation:resolveany', $context) or (has_capability('mod/consultation:resolve', $context) and ($USER->id == $inquiry->userfrom or $USER->id == $inquiry->userto)));
    $canreopen    = (has_capability('mod/consultation:reopenany', $context) or (has_capability('mod/consultation:reopen', $context) and ($USER->id == $inquiry->userfrom or $USER->id == $inquiry->userto)));
    $caninterrupt = ($USER->id != $inquiry->userfrom and $USER->id != $inquiry->userto and has_capability('mod/consultation:interrupt', $context));
    $canreply     = (($USER->id == $inquiry->userfrom or $USER->id == $inquiry->userto) and !$inquiry->resolved);

    $posts     = $DB->get_records('consultation_posts', array('inquiryid'=>$inquiry->id), 'timecreated');
    $firstpost = reset($posts);
    $count     = count($posts);

    // cache list of participants
    $users = array($inquiry->userfrom => $inquiry->userfrom, $inquiry->userto => $inquiry->userto);
    foreach ($posts as $post) {
        $users[$post->userid] = $post->userid;
    }
    $users = consultation_load_users($users);

    $userfrom = $users[$inquiry->userfrom];
    $userto   = $users[$inquiry->userto];

    $strme = get_string('fromme', 'mod_consultation');

    if ($inquiry->userfrom == $USER->id) {
        $a = new object();
        $a->subject  = format_string($inquiry->subject);
        $a->fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$inquiry->userto.'&amp;course='.$course->id.'">'.fullname($users[$inquiry->userto]).'</a>';

        echo $OUTPUT->heading(get_string('fullsubjectfromme', 'mod_consultation', $a), 2);
        echo '<div class="participants">';
        echo $OUTPUT->user_picture($userto, array('courseid'=>$course->id, 'size'=>100));
        echo '</div>';

    } else if ($inquiry->userto == $USER->id) {
        $a = new object();
        $a->subject  = format_string($inquiry->subject);
        $a->fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$inquiry->userfrom.'&amp;course='.$course->id.'">'.fullname($users[$inquiry->userfrom]).'</a>';

        echo $OUTPUT->heading(get_string('fullsubjecttome', 'mod_consultation', $a), 2);
        echo '<div class="participants">';
        echo $OUTPUT->user_picture($userfrom, array('courseid'=>$course->id, 'size'=>100));
        echo '</div>';

    } else {
        $a = new object();
        $a->subject  = format_string($inquiry->subject);
        $a->fromname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userfrom->id.'&amp;course='.$course->id.'">'.fullname($userfrom).'</a>';
        $a->toname   = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userto->id.'&amp;course='.$course->id.'">'.fullname($userto).'</a>';

        echo $OUTPUT->heading(get_string('fullsubjectothers', 'mod_consultation', $a), 2);

        echo '<div class="participants">';
        echo $OUTPUT->user_picture($userto, array('courseid'=>$course->id, 'size'=>100));
        echo $OUTPUT->user_picture($userfrom, array('courseid'=>$course->id, 'size'=>100));
        echo '</div>';
    }

    echo '<table cellspacing="0" class="consultationinquiry generaltable boxaligncenter">';
    echo '<tr><th class="usercolumn header c0">'.get_string('user').'</th><th class="messagecolumn header c1">'.get_string('message', 'mod_consultation').'</th></tr>';

    foreach ($posts as $post) {
        echo '<tr class="r0">';
        $unread = '';
        if (!$post->seenon and $post->userid != $USER->id and ($inquiry->userfrom == $USER->id or $inquiry->userto == $USER->id)) {
            $unread = 'unread';
        }
        if ($post->userid == $inquiry->userfrom) {
            $userclass = 'userfrom';
        } else if ($post->userid == $inquiry->userto) {
            $userclass = 'userto';
        } else {
            $userclass = 'interruption';
        }
        echo '<td class="'.$userclass.' cell c0">';
        if ($post->userid == $USER->id) {
            $fullname = $strme;
        } else {
            $fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$post->userid.'&amp;course='.$course->id.'">'.fullname($users[$post->userid], $caviewfull).'</a>';
        }
        echo '<span class="byuser">'.$fullname.'</span>';
        echo '<span class="modified '.$unread.'">'.userdate($post->timemodified).'</span>';
        echo '</td>';

        echo '<td class="content cell c1">';
        echo '<a id="e'.$post->id.'"></a>';
        $options = (object)array('para'=>false);
        echo format_text($post->message, $post->messageformat, $options, $course->id);
        if ($post->attachment) {
            echo '<div class="attachment">';
            consultation_print_attachment($post, $inquiry, $cm, $consultation, $course);
            echo '</div>';
        }
        $commands = array();
        $untilwarning = '';

        if ($fullview) {
            if ($post->userid == $USER->id and $consultation->edittime and $post->timecreated + ($consultation->edittime * 60) > time()) {
                $untilwarning = get_string('untilwarning', 'mod_consultation', userdate($post->timecreated + ($consultation->edittime * 60)));
                $commands[] = '<a href="post.php?inquiryid='.$inquiry->id.'&amp;id='.$post->id.'">'.get_string('edit').'</a>';
            } else if ($candeleteany) {
                if ($count == 1 or $post->id != $firstpost->id) {
                    $commands[] = '<a href="delete.php?id='.$post->id.'">'.get_string('delete').'</a>';
                }
            }
        }

        if ($commands) {
            echo '<div class="commands">';
            if ($commands) {
                echo implode(' | ', $commands);
            }
            if ($untilwarning !== '') {
                echo '<div class="untilwarning dimmed_text">'.$untilwarning.'</div>';
            }
            echo '</div>';
        }
        echo '</td></tr>';
    }
    echo '</table>';

    if ($fullview) {
        if ($inquiry->resolved) {
            if ($canreopen) {
                echo '<div class="actionbuttons">';
                echo $OUTPUT->single_button(new moodle_url('/mod/consultation/inquiry.php', array('id'=>$inquiry->id, 'action'=>'reopen')), get_string('reopeninquiry', 'mod_consultation'));
                echo '</div>';
            }
        } else {
            echo '<div class="actionbuttons">';
            echo $OUTPUT->single_button(new moodle_url('/mod/consultation/inquiry.php', array('id'=>$inquiry->id)), get_string('refresh', 'mod_consultation'));

            if ($canresolve) {
                echo $OUTPUT->single_button(new moodle_url('/mod/consultation/inquiry.php', array('id'=>$inquiry->id, 'action'=>'resolve')), get_string('resolveinquiry', 'mod_consultation'));
            }
            if ($caninterrupt) {
                echo $OUTPUT->single_button(new moodle_url('/mod/consultation/post.php', array('inquiryid'=>$inquiry->id)), get_string('interrupt', 'mod_consultation'));
            }
            if ($canreply) {
                require_once('post_form.php');
                $post = new object();
                $post->inquiryid = $inquiry->id;
                $mform = new mod_consultation_post_form('post.php', array('current'=>$post, 'inquiry'=>$inquiry, 'consultation'=>$consultation, 'cm'=>$cm, 'course'=>$course, 'full'=>false));
                $mform->display();
            }
            echo '</div>';
        }
    }
}

/**
 * Print list of my inquiries with other people, includes paging
 * @param string $type - open, resolved and unread
 * @param $consultation
 * @param $cm
 * @param $course
 * @param string $baseurl page url, needed for paging
 * @param array $urlparams url parameters added to $baseurl
 * @return void
 */
function consultation_print_my_inquiries($type, $consultation, $cm, $course, $baseurl, $urlparams) {
    global $CFG, $USER, $OUTPUT;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $caviewfull = has_capability('moodle/site:viewfullnames', $context);

    $page    = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 20, PARAM_INT);
    $sort    = optional_param('sort', 'timemodified', PARAM_ALPHA);
    $dir     = optional_param('dir', 'DESC', PARAM_ALPHA);

    //must whitelist sort and dir!
    $sort = in_array($sort, array('timemodified', 'timecreated', 'subject', 'userwith')) ? $sort : 'timemodified';
    $dir  = in_array($dir, array('ASC', 'DESC')) ? $dir : 'DESC';
    if ($sort === 'userwith') {
        $orderby = "u.lastname $dir, u.firstname $dir";
    } else {
        $orderby = "c.$sort $dir";
    }

    if ($type === 'unread') {
        list($inquirycount, $inquiries) = consultation_get_my_unread_inquiries($consultation, $USER->id, $page, $perpage, $orderby);
    } else if ($type === 'resolved') {
        list($inquirycount, $inquiries) = consultation_get_my_resolved_inquiries($consultation, $USER->id, $page, $perpage, $orderby);
    } else { // open
        list($inquirycount, $inquiries) = consultation_get_my_open_inquiries($consultation, $USER->id, $page, $perpage, $orderby);
    }

    if (!$inquirycount) {
        echo $OUTPUT->notification(get_string('noinquiries', 'mod_consultation'));
        return;
    }

    $url = $baseurl.'?';
    foreach ($urlparams as $key=>$value) {
        $url .= "$key=$value&amp;";
    }
    $pagingurl = "$url&amp;sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;";
    $sorturl   = "$url&amp;page=$page&amp;perpage=$perpage&amp;";

    if ($inquirycount > $perpage) {
        $pagingbar = new paging_bar($inquirycount, $page, $perpage, $pagingurl, 'page');
        echo $OUTPUT->render($pagingbar);
    }

    $columns = array();
    $columns['userwith']     = get_string('inquirywithuser', 'mod_consultation');
    $columns['subject']      = get_string('subject', 'mod_consultation');
    $columns['timecreated']  = get_string('inquiriestart', 'mod_consultation');
    $columns['timemodified'] = get_string('inquirylast', 'mod_consultation');
    $columns['unreadcount']  = get_string('inquiriesunreadcount', 'mod_consultation');

    foreach ($columns as $column=>$string) {
        if ($column == 'unreadcount') {
            continue;
        }
        if ($sort != $column) {
            $columnicon = '';
            if ($column == 'timemodified' or $column == 'timecreated') {
                $columndir = 'DESC';
            } else {
                $columndir = 'ASC';
            }
        } else {
            $columndir = ($dir == 'ASC') ? 'DESC' : 'ASC';
            if ($column == 'timemodified' or $column == 'timecreated') {
                $columnicon = ($dir == 'ASC') ? 'up' : 'down';
            } else {
                $columnicon = ($dir == 'ASC') ? 'down' : 'up';
            }
            $columnicon = ' <img src="'.$OUTPUT->pix_url('t/'.$columnicon).'" class="smallicon" alt="" />';
        }
        $columns[$column] = "<a href=\"{$sorturl}sort=$column&amp;dir=$columndir\">".$string."</a>$columnicon";
    }

    $data  = array();
    $users = array($USER->id=>$USER->id);

    // preload all users
    foreach ($inquiries as $inquiry) {
        $users[$inquiry->userwith] = $inquiry->userwith;
    }
    $users = consultation_load_users($users);

    foreach ($inquiries as $inquiry) {
        $line = array();
        $userid = $inquiry->userwith;
        $fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userid.'&amp;course='.$course->id.'">'.fullname($users[$userid], $caviewfull).'</a>';
        $picture  = $OUTPUT->user_picture($users[$userid], array('courseid'=>$course->id));
        $line[] = "$picture $fullname";
        $line[] = "<a href=\"$CFG->wwwroot/mod/consultation/inquiry.php?id=$inquiry->id\">".format_string($inquiry->subject).'</a>';
        $line[] = userdate($inquiry->timecreated);
        $line[] = userdate($inquiry->timemodified);
        if ($inquiry->unread) {
            $line[] = '<span class="unread">'.$inquiry->unread.'/'.$inquiry->total.'</span>';
        } else {
            $line[] = $inquiry->unread.'/'.$inquiry->total;
        }

        $data[] = $line;
    }
    $table = new html_table();
    $table->head  = $columns;
    $table->size  = array('20%', '30%', '20%', '20%', '10%');
    $table->align = array('left', 'left', 'left', 'left', 'left', 'center');
    $table->width = '95%';
    $table->data  = $data;

    echo html_writer::table($table);

    if ($inquirycount > $perpage) {
        $pagingbar = new paging_bar($inquirycount, $page, $perpage, $pagingurl, 'page');
        echo $OUTPUT->render($pagingbar);
    }
}

/**
 * Print list of my inquiries with other people, includes paging
 * @param string $type - open or resolved
 * @param $consultation
 * @param $cm
 * @param $course
 * @param string $baseurl page url, needed for paging
 * @param array $urlparams url parameters added to $baseurl
 * @return void
 */
function consultation_print_others_inquiries($type, $consultation, $cm, $course, $baseurl, $urlparams) {
    global $CFG, $USER, $OUTPUT;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $caviewfull = has_capability('moodle/site:viewfullnames', $context);

    $page    = optional_param('page', 0, PARAM_INT);
    $perpage = optional_param('perpage', 20, PARAM_INT);
    $sort    = optional_param('sort', 'timemodified', PARAM_ALPHA);
    $dir     = optional_param('dir', 'DESC', PARAM_ALPHA);
    //must whitelist sort and dir!
    $sort = in_array($sort, array('timemodified', 'timecreated', 'subject', 'userfrom', 'userto')) ? $sort : 'timemodified';
    $dir  = in_array($dir, array('ASC', 'DESC')) ? $dir : 'DESC';
    if ($sort === 'userfrom') {
        $orderby = "uf.lastname $dir, uf.firstname $dir";
    } else if ($sort === 'userto') {
        $orderby = "ut.lastname $dir, ut.firstname $dir";
    } else {
        $orderby = "c.$sort $dir";
    }

    if ($type === 'resolved') {
        list($inquirycount, $inquiries) = consultation_get_others_resolved_inquiries($consultation, $USER->id, $page, $perpage, $orderby);
    } else { // open
        list($inquirycount, $inquiries) = consultation_get_others_open_inquiries($consultation, $USER->id, $page, $perpage, $orderby);
    }

    if (!$inquirycount) {
        echo $OUTPUT->notification(get_string('noinquiries', 'mod_consultation'));
        return;
    }

    $url = $baseurl.'?';
    foreach ($urlparams as $key=>$value) {
        $url .= "$key=$value&amp;";
    }
    $pagingurl = "$url&amp;sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;";
    $sorturl   = "$url&amp;page=$page&amp;perpage=$perpage&amp;";

    if ($inquirycount > $perpage) {
        $pagingbar = new paging_bar($inquirycount, $page, $perpage, $pagingurl, 'page');
        echo $OUTPUT->render($pagingbar);
    }

    $columns = array();
    $columns['userfrom']     = get_string('inquiryfromuser', 'mod_consultation');
    $columns['userto']       = get_string('inquirytouser', 'mod_consultation');
    $columns['subject']      = get_string('subject', 'mod_consultation');
    $columns['timecreated']  = get_string('inquiriestart', 'mod_consultation');
    $columns['timemodified'] = get_string('inquirylast', 'mod_consultation');

    foreach ($columns as $column=>$string) {
        if ($sort != $column) {
            $columnicon = '';
            if ($column == 'timemodified' or $column == 'timecreated') {
                $columndir = 'DESC';
            } else {
                $columndir = 'ASC';
            }
        } else {
            $columndir = ($dir == 'ASC') ? 'DESC' : 'ASC';
            if ($column == 'timemodified' or $column == 'timecreated') {
                $columnicon = ($dir == 'ASC') ? 'up' : 'down';
            } else {
                $columnicon = ($dir == 'ASC') ? 'down' : 'up';
            }
            $columnicon = ' <img src="'.$OUTPUT->pix_url('t/'.$columnicon).'" class="smallicon" alt="" />';
        }
        $columns[$column] = "<a href=\"{$sorturl}sort=$column&amp;dir=$columndir\">".$string."</a>$columnicon";
    }

    // preload all users
    $users = array();
    foreach ($inquiries as $inquiry) {
        $users[$inquiry->userto]   = $inquiry->userto;
        $users[$inquiry->userfrom] = $inquiry->userfrom;
    }
    $users = consultation_load_users($users);

    $data  = array();
    foreach ($inquiries as $inquiry) {
        $line = array();
        $fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$inquiry->userfrom.'&amp;course='.$course->id.'">'.fullname($users[$inquiry->userfrom], $caviewfull).'</a>';
        $picture  = $OUTPUT->user_picture($users[$inquiry->userfrom], array('courseid'=>$course->id));
        $line[] = "$picture $fullname";
        $fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$inquiry->userto.'&amp;course='.$course->id.'">'.fullname($users[$inquiry->userto], $caviewfull).'</a>';
        $picture  = $OUTPUT->user_picture($users[$inquiry->userto], array('courseid'=>$course->id));
        $line[] = "$picture $fullname";
        $line[] = "<a href=\"$CFG->wwwroot/mod/consultation/inquiry.php?id=$inquiry->id\">".format_string($inquiry->subject).'</a>';
        $line[] = userdate($inquiry->timecreated);
        $line[] = userdate($inquiry->timemodified);

        $data[] = $line;
    }
    $table = new html_table();
    $table->head  = $columns;
    $table->size  = array('20%', '20%', '30%', '15%', '15%');
    $table->align = array('left', 'left', 'left', 'left', 'left');
    $table->width = '95%';
    $table->data  = $data;

    echo html_writer::table($table);

    if ($inquirycount > $perpage) {
        $pagingbar = new paging_bar($inquirycount, $page, $perpage, $pagingurl, 'page');
        echo $OUTPUT->render($pagingbar);
    }
}

/**
 * Print attachment link or as image
 * @param object $post
 * @param object $inquiry
 * @param object $cm
 * @param object $consultation
 * @param object $course
 * @return void
 */
function consultation_print_attachment($post, $inquiry, $cm, $consultation, $course) {
    global $CFG, $OUTPUT;
    require_once($CFG->dirroot.'/lib/filelib.php');

    $fs = get_file_storage();
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (!$files = $fs->get_area_files($context->id, 'mod_forum', 'attachment', $post->id, "timemodified", false)) {
        return;
    }

    foreach ($files as $file) {
        $mimetype = $file->get_mimetype();
        $iconimage = '<img src="'.$OUTPUT->pix_url(file_mimetype_icon($mimetype)).'" class="icon" alt="'.$mimetype.'" />';
        $path = moodle_url::make_pluginfile_url($context->id, 'mod_consultation', 'attachment', $post->id, $file->get_filepath(), $file->get_filename());
        echo '<br /><a href="'.$path.'">'.$iconimage.' '.s($file->get_filename()).'</a>';
    }
}

/**
 * Mark inquiry read by me if needed
 * @param object $inquiry
 * @param object $consultation
 * @param object $cm
 * @param object $course
 * @return void
 */
function consultation_mark_inquiry_read($inquiry, $consultation, $cm, $course) {
    global $USER, $DB;

    if ($inquiry->userfrom <> $USER->id and $inquiry->userto <> $USER->id) {
        // oh, I am not participant!
        return;
    }

    $timenow = time();

    $sql = "UPDATE {consultation_posts}
               SET seenon = :timenow
             WHERE inquiryid = :iid AND userid <> :uid AND seenon IS NULL";
    $DB->execute($sql, array('timenow'=>$timenow, 'uid'=>$USER->id, 'iid'=>$inquiry->id));
}

/**
 * Make sure no guest is allowed to do anything in consultation module
 *
 * @param object $consultation
 * @param object $cm
 * @param object $course
 * @return void
 */
function consultation_no_guest_access($consultation, $cm, $course) {
    global $CFG, $OUTPUT, $USER;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (!isloggedin() or isguestuser()) {
        echo $OUTPUT->header();

        $loginroot = get_login_url();

        echo $OUTPUT->confirm(get_string('noguests', 'mod_consultation').'<br /><br />'.get_string('liketologin'),
                $loginroot, $CFG->wwwroot.'/course/view.php?id='.$course->id);

        echo $OUTPUT->footer();
        exit;

    } else if (has_capability('moodle/course:view', $context)) {
        // inspector? let them in

    } else if (!is_enrolled($context, $USER, '', true)) {
        // temporary guest course access
        echo $OUTPUT->header();

        echo $OUTPUT->confirm(get_string('noguests', 'mod_consultation').'<br /><br />'.get_string('enrolme', '', format_string($course->shortname)),
            $CFG->wwwroot.'/course/enrol/index.php?id='.$course->id, $CFG->wwwroot.'/course/view.php?id='.$course->id);

        echo $OUTPUT->footer();
        exit;
    }
}

/**
 * Notify the participants if needed
 *
 * @param object $post
 * @param bool $newinquiry true if new inquiry, false if not
 * @param object $inquiry
 * @param object $consultation
 * @param object $cm
 * @param object $course
 * @return void
 */
function consultation_notify($post, $newinquiry, $inquiry, $consultation, $cm, $course) {
    global $CFG, $USER, $SITE, $DB;

    if ($consultation->notify == 0) {
        // no notification allowed, do not even set notified flag
        // because in future delayed bulk notification may be added
        return;
    }

    $a = new object();
    $a->inquiry      = format_string($inquiry->subject);
    $a->consultation = format_string($consultation->name);
    $a->from         = fullname($USER);
    $a->course       = $course->shortname;
    $a->site         = $SITE->shortname;
    $a->url          = "$CFG->wwwroot/mod/consultation/inquiry.php?id=$inquiry->id";

    if ($newinquiry) {
        $subject = get_string('mailnewsubject', 'mod_consultation', $a);
        $message = get_string('mailnewmessage', 'mod_consultation', $a);

    } else {
        $subject = get_string('mailpostsubject', 'mod_consultation', $a);
        $message = get_string('mailpostmessage', 'mod_consultation', $a);
    }

    if ($USER->id != $inquiry->userto) {
        $user = $DB->get_record('user', array('id'=>$inquiry->userto));
        email_to_user($user, $USER, $subject, $message);
    }
    if ($USER->id != $inquiry->userfrom) {
        $user = $DB->get_record('user', array('id'=>$inquiry->userfrom));
        email_to_user($user, $USER, $subject, $message);
    }

    $DB->set_field('consultation_posts', 'notified', 1, array('id'=>$post->id));
}

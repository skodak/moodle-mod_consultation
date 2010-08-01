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

require_once("$CFG->dirroot/mod/consultation/lib.php");

// SQL FUNCTIONS ///////////////////////////////////////////////////////////////////

/**
 * Count number of inquiries started by a give user.
 * @param object $consultation
 * @param int $userid
 * @return int
 */
function consultation_count_started_inquiries($consultation, $userid) {
    return count_records('consultation_inquiries', 'consultationid', $consultation->id, 'userfrom', $userid);
}

/**
 * Load user info
 * @param array $userids
 * @return array of object
 */
function consultation_load_users($userids) {
    if (empty($userids)) {
        return array();
    }
    $userids = (array)$userids;
    $chunks = array_chunk($userids, 50);
    $result = array();
    foreach ($chunks as $chunk) {
        if ($users = get_records_select('user', 'id IN ('.implode(',', $userids).')', 'lastname, firstname', 'id, username, firstname, lastname, picture, imagealt, idnumber')) {
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
    global $CFG;

    $sql = "SELECT COUNT('x')
              FROM {$CFG->prefix}consultation_inquiries c
              JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = $consultation->id AND c.resolved = 0 AND uf.id <> $userid AND ut.id <> $userid";
    if (!$inquirycount = count_records_sql($sql)) {
        return array(0, array());
    }
    $sql = "SELECT c.id, c.userfrom, c.userto, c.subject, c.resolved, c.timecreated, c.timemodified,
                   (SELECT COUNT('x') FROM {$CFG->prefix}consultation_posts e2 WHERE e2.inquiryid = c.id) AS total
              FROM {$CFG->prefix}consultation_inquiries c
              JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = $consultation->id AND c.resolved = 0 AND uf.id <> $userid AND ut.id <> $userid
          ORDER BY $orderby";
    if (!$inquiries = get_records_sql($sql, $page*$perpage, $perpage)) {
        $inquiries = array();
    }
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
    global $CFG;

    $sql = "SELECT COUNT('x')
              FROM {$CFG->prefix}consultation_inquiries c
              JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = $consultation->id AND c.resolved = 1 AND uf.id <> $userid AND ut.id <> $userid";
    if (!$inquirycount = count_records_sql($sql)) {
        return array(0, array());
    }
    $sql = "SELECT c.id, c.userfrom, c.userto, c.subject, c.resolved, c.timecreated, c.timemodified,
                   (SELECT COUNT('x') FROM {$CFG->prefix}consultation_posts e2 WHERE e2.inquiryid = c.id) AS total
              FROM {$CFG->prefix}consultation_inquiries c
              JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = $consultation->id AND c.resolved = 1 AND uf.id <> $userid AND ut.id <> $userid
          ORDER BY $orderby";
    if (!$inquiries = get_records_sql($sql, $page*$perpage, $perpage)) {
        $inquiries = array();
    }
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
    global $CFG;

    $sql = "SELECT COUNT('x')
              FROM {$CFG->prefix}consultation_inquiries c
              JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = $consultation->id AND c.resolved = 0 AND (uf.id = $userid OR ut.id = $userid)";
    if (!$inquirycount = count_records_sql($sql)) {
        return array(0, array());
    }
    $sql = "SELECT c.id, c.userwith, c.subject, c.resolved, c.timecreated, c.timemodified,
                   (SELECT COUNT('x') FROM {$CFG->prefix}consultation_posts e1 WHERE e1.inquiryid = c.id AND e1.seenon IS NULL AND userid <> $userid) AS unread,
                   (SELECT COUNT('x') FROM {$CFG->prefix}consultation_posts e2 WHERE e2.inquiryid = c.id) AS total
              FROM (SELECT cx.*, CASE cx.userfrom WHEN $userid THEN cx.userto ELSE cx.userfrom END AS userwith
                     FROM {$CFG->prefix}consultation_inquiries cx
                     JOIN {$CFG->prefix}user uf ON (uf.id = cx.userfrom AND uf.deleted = 0)
                     JOIN {$CFG->prefix}user ut ON (ut.id = cx.userto AND ut.deleted = 0)
                    WHERE cx.consultationid = $consultation->id AND cx.resolved = 0 AND (uf.id = $userid OR ut.id = $userid)
                   ) c
              JOIN {$CFG->prefix}user u ON (u.id = c.userwith)
          ORDER BY $orderby";
    $inquiries = get_records_sql($sql, $page*$perpage, $perpage);

    if (!$inquiries) {
        $inquiries = array();
    }
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
    global $CFG;

    $sql = "SELECT COUNT('x')
              FROM {$CFG->prefix}consultation_inquiries c
              JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = $consultation->id AND (uf.id = $userid OR ut.id = $userid)
                   AND EXISTS(SELECT 'x'
                                FROM {$CFG->prefix}consultation_posts e3
                               WHERE e3.inquiryid = c.id AND e3.seenon IS NULL AND e3.userid <> $userid)";
    if (!$inquirycount = count_records_sql($sql)) {
        return array(0, array());
    }
    $sql = "SELECT c.id, c.userwith, c.subject, c.resolved, c.timecreated, c.timemodified,
                   (SELECT COUNT('x') FROM {$CFG->prefix}consultation_posts e1 WHERE e1.inquiryid = c.id AND e1.seenon IS NULL AND e1.userid <> $userid) AS unread,
                   (SELECT COUNT('x') FROM {$CFG->prefix}consultation_posts e2 WHERE e2.inquiryid = c.id) AS total
              FROM (SELECT cx.*, CASE cx.userfrom WHEN $userid THEN cx.userto ELSE cx.userfrom END AS userwith
                     FROM {$CFG->prefix}consultation_inquiries cx
                     JOIN {$CFG->prefix}user uf ON (uf.id = cx.userfrom AND uf.deleted = 0)
                     JOIN {$CFG->prefix}user ut ON (ut.id = cx.userto AND ut.deleted = 0)
                    WHERE cx.consultationid = $consultation->id AND (uf.id = $userid OR ut.id = $userid)
                           AND EXISTS(SELECT 'x'
                                        FROM {$CFG->prefix}consultation_posts e3
                                       WHERE e3.inquiryid = cx.id AND e3.seenon IS NULL AND e3.userid <> $userid)
                    ) c
               JOIN {$CFG->prefix}user u ON (u.id = c.userwith)
           ORDER BY $orderby";
    $inquiries = get_records_sql($sql, $page*$perpage, $perpage);

    if (!$inquiries) {
        $inquiries = array();
    }
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
    global $CFG;

    $sql = "SELECT COUNT('x')
              FROM {$CFG->prefix}consultation_inquiries c
              JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = $consultation->id AND c.resolved = 1 AND (uf.id = $userid OR ut.id = $userid)";
    if (!$inquirycount = count_records_sql($sql)) {
        return array(0, array());
    }
    $sql = "SELECT c.id, c.userwith, c.subject, c.resolved, c.timecreated, c.timemodified,
                   (SELECT COUNT('x') FROM {$CFG->prefix}consultation_posts e1 WHERE e1.inquiryid = c.id AND e1.seenon IS NULL AND userid <> $userid) AS unread,
                   (SELECT COUNT('x') FROM {$CFG->prefix}consultation_posts e2 WHERE e2.inquiryid = c.id) AS total
              FROM (SELECT cx.*, CASE cx.userfrom WHEN $userid THEN cx.userto ELSE cx.userfrom END AS userwith
                     FROM {$CFG->prefix}consultation_inquiries cx
                     JOIN {$CFG->prefix}user uf ON (uf.id = cx.userfrom AND uf.deleted = 0)
                     JOIN {$CFG->prefix}user ut ON (ut.id = cx.userto AND ut.deleted = 0)
                    WHERE cx.consultationid = $consultation->id AND cx.resolved = 1 AND (uf.id = $userid OR ut.id = $userid)
                   ) c
              JOIN {$CFG->prefix}user u ON (u.id = c.userwith)
          ORDER BY $orderby";
    $inquiries = get_records_sql($sql, $page*$perpage, $perpage);

    if (!$inquiries) {
        $inquiries = array();
    }
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
    global $CFG;

    $counts = new object();

    // my resolved consultations
    $sql = "SELECT COUNT('x')
              FROM {$CFG->prefix}consultation_inquiries c
              JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = $consultationid AND c.resolved = 1 AND (uf.id = $userid OR ut.id = $userid)";
    $counts->myresolved = count_records_sql($sql);

    // my open consultations
    $sql = "SELECT COUNT('x')
              FROM {$CFG->prefix}consultation_inquiries c
              JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = $consultationid AND c.resolved = 0 AND (uf.id = $userid OR ut.id = $userid)";
    $counts->myopen = count_records_sql($sql);

    // my unread posts - open and resolved
    $sql = "SELECT COUNT('x')
              FROM {$CFG->prefix}consultation_inquiries c
              JOIN {$CFG->prefix}consultation_posts e ON (e.inquiryid = c.id)
              JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
              JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
             WHERE c.consultationid = $consultationid AND (uf.id = $userid OR ut.id = $userid)
                   AND e.userid <> $userid AND e.seenon IS NULL";
    if ($ignoreunreadin) {
        // this is a special case for printing of inquiries,
        // the seenon flag is set immediately after display
        $sql = "$sql AND c.id <> $ignoreunreadin";
    }
    $counts->myunread = count_records_sql($sql);

    $counts->othersresolved = null;
    $counts->othersopen = null;
    if ($others) {
        // resolved consultations of others
        $sql = "SELECT COUNT('x')
                  FROM {$CFG->prefix}consultation_inquiries c
                  JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
                  JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
                 WHERE c.consultationid = $consultationid AND c.resolved = 1 AND uf.id <> $userid AND ut.id <> $userid";
        $counts->othersresolved = count_records_sql($sql);

        // open consultations of others
        $sql = "SELECT COUNT('x')
                  FROM {$CFG->prefix}consultation_inquiries c
                  JOIN {$CFG->prefix}user uf ON (uf.id = c.userfrom AND uf.deleted = 0)
                  JOIN {$CFG->prefix}user ut ON (ut.id = c.userto AND ut.deleted = 0)
                 WHERE c.consultationid = $consultationid AND c.resolved = 0 AND uf.id <> $userid AND ut.id <> $userid";
        $counts->othersopen = count_records_sql($sql);
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
        if (!$candidates = get_users_by_capability($context, 'moodle/course:view', 'u.id,u.lastname,u.firstname',
                                                  'u.lastname ASC, u.firstname ASC', '', '', $groupid, '', false, true, false)) {
            return array(array(), array());
        }

    } else {
        if (!$candidates = get_users_by_capability($context, 'mod/consultation:answer', 'u.id,u.lastname,u.firstname',
                                                  'u.lastname ASC, u.firstname ASC', '', '', $groupid, '', false, true, false)) {
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
    if ($existing = get_records_select("consultation_inquiries",
                                       "consultationid = $consultation->id  AND (userfrom = $user->id OR userto = $user->id)",
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
 * Print tabs
 * @param string $currenttab
 * @param string $mode
 * @param int $ignoreunreadin
 * @param object $consultation
 * @param object $cm
 * @param object $course
 * @return void
 */
function consultation_print_tabs($currenttab, $mode, $ignoreunreadin, $consultation, $cm, $course) {
    global $USER;

    $tabs = array();

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $viewall = has_capability('mod/consultation:viewany', $context);

    $counts = consultation_get_counts($USER->id, $consultation->id, $viewall, $ignoreunreadin);

    $activetwo = null;

    $row = array();
    if (has_capability('mod/consultation:open', $context) or has_capability('mod/consultation:openany', $context)) {
        $row[] = new tabobject('open', "open.php?id=$cm->id", get_string('tabopen', 'consultation'));
    }
    if ($counts->myunread) {
        $row[] = new tabobject('unread', "unread.php?id=$cm->id", get_string('tabunread', 'consultation', $counts->myunread));
    }
    if ($viewall) {
        $row[] = new tabobject('view', "view.php?id=$cm->id", get_string('tabview', 'consultation'));
    } else {
        $row[] = new tabobject('view', "view.php?id=$cm->id", get_string('tabviewany', 'consultation', $counts->myopen));
    }
    if ($counts->myresolved or $counts->othersresolved or $currenttab === 'resolved') {
        if ($viewall) {
            $row[] = new tabobject('resolved', "resolved.php?id=$cm->id", get_string('tabresolved', 'consultation'));
        } else {
            $row[] = new tabobject('resolved', "resolved.php?id=$cm->id", get_string('tabresolvedany', 'consultation', $counts->myresolved));
        }
    }

    $tabs[] = $row;

    if ($viewall and $currenttab === 'view') {
        $row = array();
        $row[] = new tabobject('viewmy', "view.php?id=$cm->id&mode=my", get_string('subtabviewmy', 'consultation', $counts->myopen));
        $row[] = new tabobject('viewothers', "view.php?id=$cm->id&mode=others", get_string('subtabviewothers', 'consultation', $counts->othersopen));
        $tabs[] = $row;
        $activetwo = array('view'.$mode);
    }

    if ($viewall and $currenttab === 'resolved') {
        $row = array();
        $row[] = new tabobject('resolvedmy', "resolved.php?id=$cm->id&mode=my", get_string('subtabresolvedmy', 'consultation', $counts->myresolved));
        $row[] = new tabobject('resolvedothers', "resolved.php?id=$cm->id&mode=others", get_string('subtabresolvedothers', 'consultation', $counts->othersresolved));
        $tabs[] = $row;
        $activetwo = array('resolved'.$mode);
    }

    print_tabs($tabs, $currenttab, $activetwo);
}

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
    global $CFG, $USER;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    $caviewfull   = has_capability('moodle/site:viewfullnames', $context);
    $candeleteany = has_capability('mod/consultation:deleteany', $context);
    $canresolve   = (has_capability('mod/consultation:resolveany', $context) or (has_capability('mod/consultation:resolve', $context) and ($USER->id == $inquiry->userfrom or $USER->id == $inquiry->userto)));
    $canreopen    = (has_capability('mod/consultation:reopenany', $context) or (has_capability('mod/consultation:reopen', $context) and ($USER->id == $inquiry->userfrom or $USER->id == $inquiry->userto)));
    $caninterrupt = ($USER->id != $inquiry->userfrom and $USER->id != $inquiry->userto and has_capability('mod/consultation:interrupt', $context));
    $canreply     = (($USER->id == $inquiry->userfrom or $USER->id == $inquiry->userto) and !$inquiry->resolved);

    $posts     = get_records('consultation_posts', 'inquiryid', $inquiry->id, 'timecreated');
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

    $strme = get_string('fromme', 'consultation');

    if ($inquiry->userfrom == $USER->id) {
        $a = new object();
        $a->subject  = format_string($inquiry->subject);
        $a->fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$inquiry->userto.'&amp;course='.$course->id.'">'.fullname($users[$inquiry->userto]).'</a>';

        print_heading(get_string('fullsubjectfromme', 'consultation', $a), '', 2);
        echo '<div class="participants">';
        print_user_picture($inquiry->userto, $course->id, NULL, 100);
        echo '</div>';

    } else if ($inquiry->userto == $USER->id) {
        $a = new object();
        $a->subject  = format_string($inquiry->subject);
        $a->fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$inquiry->userfrom.'&amp;course='.$course->id.'">'.fullname($users[$inquiry->userfrom]).'</a>';

        print_heading(get_string('fullsubjecttome', 'consultation', $a), '', 2);
        echo '<div class="participants">';
        print_user_picture($users[$inquiry->userfrom], $course->id, NULL, 100);
        echo '</div>';

    } else {
        $a = new object();
        $a->subject  = format_string($inquiry->subject);
        $a->fromname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userfrom->id.'&amp;course='.$course->id.'">'.fullname($userfrom).'</a>';
        $a->toname   = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$userto->id.'&amp;course='.$course->id.'">'.fullname($userto).'</a>';

        print_heading(get_string('fullsubjectothers', 'consultation', $a), '', 2);

        echo '<div class="participants">';
        print_user_picture($userfrom, $course->id, NULL, 100);
        print_user_picture($userto, $course->id, NULL, 100);
        echo '</div>';
    }

    echo '<table cellspacing="0" class="consultationinquiry generaltable boxaligncenter">';
    echo '<tr><th class="usercolumn header c0">'.get_string('user').'</th><th class="messagecolumn header c1">'.get_string('message', 'consultation').'</th></tr>';

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
                $untilwarning = get_string('untilwarning', 'consultation', userdate($post->timecreated + ($consultation->edittime * 60)));
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
                print_single_button('inquiry.php', array('id'=>$inquiry->id, 'action'=>'reopen'), get_string('reopeninquiry', 'consultation'));
                echo '</div>';
            }
        } else {
            echo '<div class="actionbuttons">';
            print_single_button('inquiry.php', array('id'=>$inquiry->id), get_string('refresh', 'consultation'));

            if ($canresolve) {
                print_single_button('inquiry.php', array('id'=>$inquiry->id, 'action'=>'resolve'), get_string('resolveinquiry', 'consultation'));
            }
            if ($caninterrupt) {
                print_single_button('post.php', array('inquiryid'=>$inquiry->id), get_string('interrupt', 'consultation'));
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
    global $CFG, $USER;

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
        notify(get_string('noinquiries', 'consultation'));
        return;
    }

    $url = $baseurl.'?';
    foreach ($urlparams as $key=>$value) {
        $url .= "$key=$value&amp;";
    }
    $pagingurl = "$url&amp;sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;";
    $sorturl   = "$url&amp;page=$page&amp;perpage=$perpage&amp;";

    if ($inquirycount > $perpage) {
        print_paging_bar($inquirycount, $page, $perpage, $pagingurl);
    }

    $columns = array();
    $columns['userwith']     = get_string('inquirywithuser', 'consultation');
    $columns['subject']      = get_string('subject', 'consultation');
    $columns['timecreated']  = get_string('inquiriestart', 'consultation');
    $columns['timemodified'] = get_string('inquirylast', 'consultation');
    $columns['unreadcount']  = get_string('inquiriesunreadcount', 'consultation');

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
            $columnicon = " <img src=\"$CFG->pixpath/t/$columnicon.gif\" alt=\"\" />";

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
        $picture  = print_user_picture($users[$userid], $course->id, null, 0, true);
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
    $table = new object();
    $table->head  = $columns;
    $table->size  = array('20%', '30%', '20%', '20%', '10%');
    $table->align = array('left', 'left', 'left', 'left', 'left', 'center');
    $table->width = '95%';
    $table->data  = $data;

    print_table($table);

    if ($inquirycount > $perpage) {
        print_paging_bar($inquirycount, $page, $perpage, $pagingurl);
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
    global $CFG, $USER;

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
        notify(get_string('noinquiries', 'consultation'));
        return;
    }

    $url = $baseurl.'?';
    foreach ($urlparams as $key=>$value) {
        $url .= "$key=$value&amp;";
    }
    $pagingurl = "$url&amp;sort=$sort&amp;dir=$dir&amp;perpage=$perpage&amp;";
    $sorturl   = "$url&amp;page=$page&amp;perpage=$perpage&amp;";

    if ($inquirycount > $perpage) {
        print_paging_bar($inquirycount, $page, $perpage, $pagingurl);
    }

    $columns = array();
    $columns['userfrom']     = get_string('inquiryfromuser', 'consultation');
    $columns['userto']       = get_string('inquirytouser', 'consultation');
    $columns['subject']      = get_string('subject', 'consultation');
    $columns['timecreated']  = get_string('inquiriestart', 'consultation');
    $columns['timemodified'] = get_string('inquirylast', 'consultation');

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
            $columnicon = " <img src=\"$CFG->pixpath/t/$columnicon.gif\" alt=\"\" />";

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
        $picture  = print_user_picture($users[$inquiry->userfrom], $course->id, null, 0, true);
        $line[] = "$picture $fullname";
        $fullname = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$inquiry->userto.'&amp;course='.$course->id.'">'.fullname($users[$inquiry->userto], $caviewfull).'</a>';
        $picture  = print_user_picture($users[$inquiry->userto], $course->id, null, 0, true);
        $line[] = "$picture $fullname";
        $line[] = "<a href=\"$CFG->wwwroot/mod/consultation/inquiry.php?id=$inquiry->id\">".format_string($inquiry->subject).'</a>';
        $line[] = userdate($inquiry->timecreated);
        $line[] = userdate($inquiry->timemodified);

        $data[] = $line;
    }
    $table = new object();
    $table->head  = $columns;
    $table->size  = array('20%', '20%', '30%', '15%', '15%');
    $table->align = array('left', 'left', 'left', 'left', 'left');
    $table->width = '95%';
    $table->data  = $data;

    print_table($table);

    if ($inquirycount > $perpage) {
        print_paging_bar($inquirycount, $page, $perpage, $pagingurl);
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
    global $CFG;
    require_once($CFG->dirroot.'/lib/filelib.php');

    if (empty($post->attachment)) {
        return;
    }

    $output = '';

    $file = $post->attachment;
    $icon = mimeinfo("icon", $file);
    $type = mimeinfo("type", $file);
    $ffurl = get_file_url(consultation_get_moddata_post_dir($post, $consultation).'/'.$post->attachment);

    $image = "<img src=\"$CFG->pixpath/f/$icon\" class=\"icon\" alt=\"\" />";

    if (in_array($type, array('image/gif', 'image/jpeg', 'image/png'))) {    // Image attachments don't get printed as links
        echo "<br /><img src=\"$ffurl\" alt=\"\" />";
    } else {
        echo "<a href=\"$ffurl\">$image</a> ";
        echo filter_text("<a href=\"$ffurl\">$file</a><br />");
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
    global $USER, $CFG;

    if ($inquiry->userfrom <> $USER->id and $inquiry->userto <> $USER->id) {
        // oh, I am not participant!
        return;
    }

    $timenow = time();

    $sql = "UPDATE {$CFG->prefix}consultation_posts
               SET seenon = $timenow
             WHERE inquiryid = $inquiry->id AND userid <> $USER->id AND seenon IS NULL";
    execute_sql($sql, false);
}

/**
 * Returns path to moddata directory for the post
 * @param $post
 * @param $consultation
 * @return string path to dir
 */
function consultation_get_moddata_post_dir($post, $consultation) {
    global $CFG;

    return "$consultation->course/$CFG->moddata/consultation/$consultation->id/$post->id";
}

/**
 * Make sure no guest is allowed to do anything in consultation module
 * @param object $consultation
 * @param object $cm
 * @param object $course
 * @return void
 */
function consultation_no_guest_access($consultation, $cm, $course) {
    global $CFG;

    $context = get_context_instance(CONTEXT_MODULE, $cm->id);

    if (!isloggedin() or isguestuser()) {
        $navigation = build_navigation('', $cm);
        print_header_simple($consultation->name, '', $navigation, '', '', true, '', navmenu($course, $cm));
        $loginroot = $CFG->wwwroot.'/login/index.php';
        if (!empty($CFG->loginhttps)) {
            $loginroot = str_replace('http:','https:', $loginroot);
        }

        notice_yesno(get_string('noguests', 'consultation').'<br /><br />'.get_string('liketologin'),
                $loginroot, $CFG->wwwroot.'/course/view.php?id='.$course->id);

        print_footer($course);
        exit;

    } else if (has_capability('moodle/legacy:guest', $context, NULL, false)) {
        // temporary guest course access
        $navigation = build_navigation('', $cm);
        print_header_simple($consultation->name, '', $navigation, '', '', true, '', navmenu($course, $cm));

        if (empty($course->metacourse) && ($course->id !== SITEID)) {
            notice_yesno(get_string('noguests', 'consultation').'<br /><br />'.get_string('enrolme', '', format_string($course->shortname)),
                $CFG->wwwroot.'/course/enrol.php?id='.$course->id, $CFG->wwwroot.'/course/view.php?id='.$course->id);
        } else {
            notify(get_string('noguests', 'consultation'));
        }

        print_footer($course);
        exit;
    }
}

/**
 * Notify the participants if needed
 * @param object $post
 * @param bool $newinquiry true if new inquiry, false if not
 * @param object $inquiry
 * @param object $consultation
 * @param object $cm
 * @param object $course
 * @return void
 */
function consultation_notify($post, $newinquiry, $inquiry, $consultation, $cm, $course) {
    global $CFG, $USER, $SITE;

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

    $from = $USER;
    if ($newinquiry) {
        $subject = get_string('mailnewsubject', 'consultation', $a);
        $message = get_string('mailnewmessage', 'consultation', $a);

    } else {
        $subject = get_string('mailpostsubject', 'consultation', $a);
        $message = get_string('mailpostmessage', 'consultation', $a);
    }

    if ($USER->id != $inquiry->userto) {
        $user = get_record('user', 'id', $inquiry->userto);
        email_to_user($user, $USER, $subject, $message);
    }
    if ($USER->id != $inquiry->userfrom) {
        $user = get_record('user', 'id', $inquiry->userfrom);
        email_to_user($user, $USER, $subject, $message);
    }

    set_field('consultation_posts', 'notified', 1, 'id', $post->id);
}
